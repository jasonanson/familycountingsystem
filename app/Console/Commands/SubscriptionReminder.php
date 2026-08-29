<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * 每日掃描訂閱服務，在下次扣款日前 N 天自動發送提醒。
 *
 * 用法：
 *   php artisan subscriptions:remind                  # 掃描所有家庭
 *   php artisan subscriptions:remind --days=3          # 提前 3 天提醒（預設 3）
 *   php artisan subscriptions:remind --days=1,3,7      # 同時抓 1/3/7 天
 *   php artisan subscriptions:remind --dry-run         # 試跑
 *
 * 重複防護：dedup_key = "subscription:{id}:remind:{YYYY-MM-DD}"
 * 已暫停 (is_paused = true) 的訂閱會被跳過。
 */
class SubscriptionReminder extends Command
{
    protected $signature = 'subscriptions:remind
                            {--days=3 : 提前幾天提醒（可多個，逗號分隔，例如 1,3,7）}
                            {--family= : 只檢查指定 family_id}
                            {--dry-run : 試跑不發通知}';

    protected $description = '每日掃描訂閱服務，在下次扣款日前對家長自動發送提醒';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $familyId = $this->option('family');
        $daysOption = (string) $this->option('days');
        $daysList = array_values(array_filter(array_map('intval', explode(',', $daysOption)), fn($d) => $d >= 0 && $d <= 60));

        if (empty($daysList)) {
            $daysList = [3];
        }
        sort($daysList);

        $today = Carbon::today();
        $this->info('[訂閱提醒] 啟動日期：' . $today->toDateString() . '，提前天數：' . implode('/', $daysList) . ($isDryRun ? ' (DRY-RUN)' : ''));

        $query = Subscription::query()->with(['family', 'account', 'category']);
        if ($familyId) {
            $query->where('family_id', (int) $familyId);
        }
        $subscriptions = $query->where('is_paused', false)->get();

        if ($subscriptions->isEmpty()) {
            $this->warn('沒有啟用中的訂閱。');
            return self::SUCCESS;
        }

        $stats = ['scanned' => 0, 'notified' => 0, 'skipped_dedup' => 0, 'skipped_future' => 0];

        foreach ($subscriptions as $sub) {
            $stats['scanned']++;
            if (! $sub->next_billing_date) {
                continue;
            }
            $nextDate = $sub->next_billing_date->copy()->startOfDay();
            $diffDays = (int) $today->diffInDays($nextDate, false); // positive = future

            if ($diffDays < 0) {
                // 已過期（理論上 convertExpense 會推延，但防呆）
                $stats['skipped_future']++;
                continue;
            }

            if (! in_array($diffDays, $daysList, true)) {
                continue;
            }

            $this->processSubscription($sub, $diffDays, $isDryRun, $stats);
        }

        $this->info(sprintf(
            '[訂閱提醒] 完成：掃描 %d 筆，已通知 %d，略過重複 %d，未到期跳過 %d',
            $stats['scanned'], $stats['notified'], $stats['skipped_dedup'], $stats['skipped_future']
        ));

        return self::SUCCESS;
    }

    private function processSubscription(Subscription $sub, int $diffDays, bool $isDryRun, array &$stats): void
    {
        $dueDate = $sub->next_billing_date->copy()->startOfDay();
        $dedupKey = md5('subscription:' . $sub->id . ':remind:' . $dueDate->toDateString());

        $alreadyNotified = Notification::where('family_id', $sub->family_id)
            ->where('type', 'subscription_reminder')
            ->where('created_at', '>=', now()->subDays(14))
            ->get()
            ->contains(function (Notification $n) use ($dedupKey) {
                $entity = $n->related_entity ?? [];
                return data_get($entity, 'dedup_key') === $dedupKey;
            });

        if ($alreadyNotified) {
            $stats['skipped_dedup']++;
            $this->line('  - skip (dup) sub=' . $sub->id);
            return;
        }

        $urgency = match (true) {
            $diffDays === 0 => 'today',
            $diffDays === 1 => 'tomorrow',
            $diffDays <= 3 => 'soon',
            default => 'upcoming',
        };

        $titlePrefix = match ($urgency) {
            'today' => '[今日扣款]',
            'tomorrow' => '[明日扣款]',
            'soon' => '[即將扣款]',
            default => '[訂閱提醒]',
        };

        $title = $titlePrefix . ' ' . $sub->name . ' NT$ ' . number_format((float) $sub->amount);
        $body = sprintf(
            "「%s」將於 %s （%s）扣款 NT\$ %s。帳戶：%s，分類：%s。%s",
            $sub->name,
            $dueDate->format('Y/m/d'),
            $this->getRelativeLabel($diffDays),
            number_format((float) $sub->amount),
            $sub->account?->name ?? '未指定',
            $sub->category?->name ?? '未分類',
            $sub->url ? "連結：" . $sub->url : ''
        );

        if ($isDryRun) {
            $this->line('  - [DRY] ' . $sub->name . ' -> ' . $title);
            return;
        }

        $parents = User::whereHas('families', function ($q) use ($sub) {
            $q->where('families.id', $sub->family_id)
              ->where('family_user.role', 'parent')
              ->where('family_user.is_active', true);
        })->get();

        if ($parents->isEmpty()) {
            $ownerId = $sub->family?->owner_user_id ?? $sub->family?->created_by_user_id;
            if ($ownerId) {
                $parents = collect([User::find($ownerId)])->filter();
            }
        }

        $count = 0;
        foreach ($parents as $parent) {
            Notification::create([
                'user_id' => $parent->id,
                'family_id' => $sub->family_id,
                'type' => 'subscription_reminder',
                'title' => $title,
                'body' => $body,
                'channel' => 'system',
                'related_entity' => [
                    'dedup_key' => $dedupKey,
                    'subscription_id' => $sub->id,
                    'days_until' => $diffDays,
                    'due_date' => $dueDate->toDateString(),
                    'amount' => (float) $sub->amount,
                ],
            ]);
            $count++;
        }

        try {
            AuditService::log(
                'subscription_reminder_sent',
                Subscription::class,
                $sub->id,
                ['days_until' => $diffDays, 'recipients' => $count],
                $title,
                $body
            );
        } catch (\Throwable $e) {
            // ignore
        }

        $stats['notified']++;
        $this->line('  - notified (' . $count . ') sub=' . $sub->id . ' in ' . $diffDays . 'd');
    }

    private function getRelativeLabel(int $diffDays): string
    {
        return match (true) {
            $diffDays === 0 => '今日',
            $diffDays === 1 => '明日',
            $diffDays <= 7 => $diffDays . ' 天後',
            $diffDays <= 30 => '約 ' . (int) ceil($diffDays / 7) . ' 週後',
            default => '約 ' . (int) ceil($diffDays / 30) . ' 個月後',
        };
    }
}
