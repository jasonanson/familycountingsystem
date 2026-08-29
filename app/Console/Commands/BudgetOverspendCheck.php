<?php

namespace App\Console\Commands;

use App\Models\Budget;
use App\Models\Notification;
use App\Models\User;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * 每日檢查家庭預算使用率，超過 alert_thresholds 或 100% 時自動寄發通知。
 *
 * 用法：
 *   php artisan budgets:check-overspend              # 跑全部家庭
 *   php artisan budgets:check-overspend --family=1   # 只跑特定家庭
 *   php artisan budgets:check-overspend --dry-run    # 試跑不發通知
 */
class BudgetOverspendCheck extends Command
{
    protected $signature = 'budgets:check-overspend
                            {--family= : 只檢查指定 family_id(預設全部)}
                            {--dry-run : 只列出會通知的預算，不寫入 DB 也不寄信}';

    protected $description = '每日檢查家庭預算使用率並對超支 / 警示預算自動發送站內通知 + Email';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $familyId = $this->option('family');

        $today = Carbon::today();
        $this->info('[預算掃描] 啟動日期：' . $today->toDateString() . ($isDryRun ? ' (DRY-RUN)' : ''));

        $query = Budget::query()->with('family');
        if ($familyId) {
            $query->where('family_id', (int) $familyId);
        }
        $budgets = $query->whereNull('parent_budget_id')
            ->where(function ($q) use ($today) {
                $q->whereNull('period_end')
                  ->orWhereDate('period_end', '>=', $today->copy()->subDays(7)->toDateString());
            })
            ->get();

        if ($budgets->isEmpty()) {
            $this->warn('沒有符合條件的預算。');
            return self::SUCCESS;
        }

        $stats = ['scanned' => 0, 'warning' => 0, 'danger' => 0, 'overspend' => 0, 'skipped_dedup' => 0];
        foreach ($budgets as $budget) {
            $stats['scanned']++;
            $result = $this->evaluateBudget($budget, $today, $isDryRun, $stats);
            if ($result) {
                $this->line('  - family=' . $budget->family_id . ' budget=' . $budget->id . ' -> ' . $result);
            }
        }

        $this->info(sprintf(
            '[預算掃描] 完成：掃描 %d 筆，warning=%d / danger=%d / overspend=%d，略過重複 %d',
            $stats['scanned'], $stats['warning'], $stats['danger'], $stats['overspend'], $stats['skipped_dedup']
        ));

        return self::SUCCESS;
    }

    private function evaluateBudget(Budget $budget, Carbon $today, bool $isDryRun, array &$stats): ?string
    {
        $spent = (float) $budget->spent_amount;
        $amount = (float) $budget->amount;
        $usagePercent = $amount > 0 ? round(($spent / $amount) * 100, 1) : 0.0;

        $isExpired = $budget->period_end && $budget->period_end->lt($today);
        if ($isExpired && $spent > $amount && $amount > 0) {
            return $this->emitNotification(
                $budget, 'danger', 'overspend',
                '[OVERSHOOT] 預算超支警示：' . $budget->family->name . ' 家庭',
                sprintf(
                    "%s 期間的家庭預算 NT\$ %s 已超支，目前累積 NT\$ %s（使用率 %.1f%%）。請儘速檢視消費明細並考慮下期調整預算額度。",
                    $budget->period_start?->format('Y/m/d') . ' ~ ' . $budget->period_end?->format('Y/m/d'),
                    number_format($amount),
                    number_format($spent),
                    $usagePercent
                ),
                $isDryRun, $stats
            );
        }

        $thresholds = $budget->alert_thresholds ?: ['warning' => 80, 'danger' => 100];
        $warningLevel = (int) ($thresholds['warning'] ?? 80);
        $dangerLevel = (int) ($thresholds['danger'] ?? 100);

        $level = null;
        if ($usagePercent >= $dangerLevel) {
            $level = 'danger';
        } elseif ($usagePercent >= $warningLevel) {
            $level = 'warning';
        }

        if (! $level) {
            return null;
        }

        return $this->emitNotification(
            $budget, $level, 'in_period',
            '[BUDGET] 預算使用率 ' . $level . '：' . ($level === 'danger' ? '已達上限' : '接近上限'),
            sprintf(
                "%s 家庭本月度預算使用率已達 %.1f%%（累積 NT\$ %s / 預算 NT\$ %s），%s。",
                $budget->family->name,
                $usagePercent,
                number_format($spent),
                number_format($amount),
                $level === 'danger' ? '請立即檢視消費' : '請注意控管'
            ),
            $isDryRun, $stats
        );
    }

    private function emitNotification(
        Budget $budget,
        string $level,
        string $scenario,
        string $title,
        string $body,
        bool $isDryRun,
        array &$stats
    ): ?string {
        // 若達到 danger 且 AI 已啟用，嘗試產生 AI 溫馨提醒
        if ($level === 'danger' && \App\Services\Ai\GeminiService::isEnabled()) {
            try {
                $spent = (float) $budget->spent_amount;
                $amount = (float) $budget->amount;
                $overspent = max(0, $spent - $amount);
                $categoryName = $budget->category?->name ?? ($budget->name ?? '家庭總預算');
                $aiAdvice = \App\Services\Ai\GeminiService::generateOverspendAdvice($categoryName, $overspent, $amount);
                if (!empty($aiAdvice)) {
                    $body .= "\n\n🤖【Gemini AI 智能省錢小叮嚀】\n" . $aiAdvice;
                }
            } catch (\Throwable $e) {
                // AI 異常不影響通知正常發送
            }
        }

        $stats[$level] = ($stats[$level] ?? 0) + 1;
        $dedupKey = md5('budget:' . $budget->id . ':' . $scenario . ':' . $level);
        $dedupWindowDays = 7;

        $alreadyNotified = Notification::where('family_id', $budget->family_id)
            ->where('type', 'budget_alert')
            ->where('created_at', '>=', now()->subDays($dedupWindowDays))
            ->get()
            ->contains(function (Notification $n) use ($dedupKey) {
                $entity = $n->related_entity ?? [];
                return data_get($entity, 'dedup_key') === $dedupKey;
            });

        if ($alreadyNotified) {
            $stats['skipped_dedup']++;
            return '略過重複 (' . $level . ')';
        }

        if ($isDryRun) {
            return '[DRY] 將寄送 ' . $level . ' 通知：' . $title;
        }

        $parents = User::whereHas('families', function ($q) use ($budget) {
            $q->where('families.id', $budget->family_id)
              ->where('family_user.role', 'parent')
              ->where('family_user.is_active', true);
        })->get();

        if ($parents->isEmpty()) {
            $ownerId = $budget->family->owner_user_id ?? $budget->family->created_by_user_id;
            if ($ownerId) {
                $parents = collect([User::find($ownerId)])->filter();
            }
        }

        $count = 0;
        foreach ($parents as $parent) {
            Notification::create([
                'user_id' => $parent->id,
                'family_id' => $budget->family_id,
                'type' => 'budget_alert',
                'title' => $title,
                'body' => $body,
                'channel' => 'system',
                'related_entity' => [
                    'dedup_key' => $dedupKey,
                    'budget_id' => $budget->id,
                    'level' => $level,
                    'scenario' => $scenario,
                    'usage_percent' => round(((float) $budget->spent_amount / max((float) $budget->amount, 1)) * 100, 1),
                ],
            ]);
            $count++;
        }

        try {
            AuditService::log(
                'budget_alert_sent',
                Budget::class,
                $budget->id,
                ['level' => $level, 'scenario' => $scenario, 'recipients' => $count],
                $title,
                $body
            );
        } catch (\Throwable $e) {
            // audit log 失敗不應阻斷通知
        }

        return '通知已發送 (' . $count . ' 位家長, ' . $level . ')';
    }
}
