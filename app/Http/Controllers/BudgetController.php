<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Family;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    /**
     * 預算總覽與月份篩選
     */
    public function index(Request $request)
    {
        if (! Auth::check()) {
            $defaultUser = User::where('account', 'parent')->first() ?? User::first();
            if ($defaultUser) {
                Auth::login($defaultUser);
            }
        }

        $user = Auth::user();
        if ($user && ! $user->current_family_id && $user->families()->exists()) {
            $user->update(['current_family_id' => $user->families()->first()->id]);
            $user->refresh();
        }

        $family = $user?->currentFamily ?? ($user ? $user->ensureHasFamily() : Family::withoutGlobalScopes()->first());
        $familyId = $family?->id;

        // 月份篩選（預設當月 Y-m）
        $month = $request->get('month', now()->format('Y-m'));
        try {
            $date = Carbon::createFromFormat('Y-m', $month);
        } catch (\Exception $e) {
            $date = now();
            $month = $date->format('Y-m');
        }

        $startOfMonth = $date->copy()->startOfMonth()->toDateString();
        $endOfMonth = $date->copy()->endOfMonth()->toDateString();
        $prevMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        // 查詢當月的全家主預算（scope === 'family' 且無 parent_budget_id）與其底下掛載的所有分類子預算
        $mainBudget = Budget::with(['subBudgets.parentBudget', 'family'])
            ->where('scope', 'family')
            ->whereNull('parent_budget_id')
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereDate('period_start', '<=', $endOfMonth)
                  ->whereDate('period_end', '>=', $startOfMonth);
            })
            ->orderByDesc('id')
            ->first();

        // 統計總預算金額、總支出金額、總使用率、總狀態
        $totalBudget = $mainBudget ? (float) $mainBudget->amount : 0.0;
        $totalSpent = $mainBudget 
            ? (float) $mainBudget->spent_amount 
            : (float) Transaction::where('type', 'expense')
                ->whereBetween('occurred_at', [Carbon::parse($startOfMonth)->startOfDay(), Carbon::parse($endOfMonth)->endOfDay()])
                ->sum('amount');
        $totalRemaining = $mainBudget ? (float) $mainBudget->remaining_amount : max(0.0, $totalBudget - $totalSpent);
        $totalUsagePercentage = $totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100, 1) : 0.0;
        $totalStatus = $totalUsagePercentage >= 100 ? 'danger' : ($totalUsagePercentage >= 80 ? 'warning' : 'normal');

        $subBudgets = $mainBudget ? $mainBudget->subBudgets : collect();

        // 載入當前家庭所有支出分類 (Category::where('type', 'expense')->...)
        $expenseCategories = Category::where(function ($q) {
                $q->where('type', 'expense')->orWhere('type', 'both');
            })
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('sort_order', 'asc')
            ->get();

        // 構建各大支出分類之預算卡片數據 ($categoryCards)
        $categoryCards = $expenseCategories->map(function ($cat) use ($subBudgets, $startOfMonth, $endOfMonth) {
            $subBudget = $subBudgets->first(function ($b) use ($cat) {
                return $b->scope === 'category' && in_array($cat->id, (array) $b->scope_target_ids);
            });

            $budgetAmount = $subBudget ? (float) $subBudget->amount : 0.0;
            
            // 計算該分類 (包含其子分類) 當月實際支出
            $catIds = array_merge([$cat->id], $cat->children ? $cat->children->pluck('id')->toArray() : []);
            $spent = (float) Transaction::where('type', 'expense')
                ->whereIn('category_id', $catIds)
                ->whereBetween('occurred_at', [Carbon::parse($startOfMonth)->startOfDay(), Carbon::parse($endOfMonth)->endOfDay()])
                ->sum('amount');

            $percentage = $budgetAmount > 0 ? round(($spent / $budgetAmount) * 100, 1) : ($spent > 0 ? 100.0 : 0.0);
            $isOver80 = $percentage >= 80.0 && $percentage < 100.0;
            $isOver100 = $budgetAmount > 0 ? ($spent >= $budgetAmount) : false;

            return [
                'id' => $subBudget?->id,
                'category_id' => $cat->id,
                'name' => $cat->name,
                'icon' => $cat->icon ?: 'category',
                'color' => $cat->color ?: '#006b5f',
                'scope_label' => '全家庭',
                'budget_amount' => $budgetAmount,
                'spent_amount' => $spent,
                'percentage' => $percentage,
                'is_over_80' => $isOver80,
                'is_over_100' => $isOver100,
                'status' => $isOver100 ? 'danger' : ($isOver80 ? 'warning' : 'normal'),
            ];
        });

        // 所有主預算列表（供歷史查詢或列表呈現）
        $budgets = Budget::with(['subBudgets', 'family'])
            ->whereNull('parent_budget_id')
            ->orderByDesc('period_start')
            ->get();

        // 為了相容現有視圖變數名稱
        $selectedMonth = $month;
        $categories = $expenseCategories;
        $totalBudgetAmount = $totalBudget;
        $totalSpentAmount = $totalSpent;
        $totalUsagePercent = $totalUsagePercentage;
        $overallHealthStatus = $totalStatus === 'danger' ? 'danger' : ($totalStatus === 'warning' ? 'warning' : 'healthy');
        $overallHealthText = $totalStatus === 'danger' ? '已超支 (超過 100%)' : ($totalStatus === 'warning' ? '已超過 80% 警戒線' : '整體預算健康');

        return view('budgets.index', compact(
            'family',
            'month',
            'selectedMonth',
            'prevMonth',
            'nextMonth',
            'mainBudget',
            'subBudgets',
            'totalBudget',
            'totalSpent',
            'totalRemaining',
            'totalUsagePercentage',
            'totalStatus',
            'totalBudgetAmount',
            'totalSpentAmount',
            'totalUsagePercent',
            'overallHealthStatus',
            'overallHealthText',
            'expenseCategories',
            'categoryCards',
            'categories',
            'budgets'
        ));
    }

    /**
     * 建立主預算與分類子預算
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'period_type' => 'nullable|in:month,quarter,year,custom',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
            'month' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'alert_thresholds' => 'nullable|array',
            'rollover' => 'nullable|boolean',
            'category_budgets' => 'nullable|array',
            'category_budgets.*' => 'nullable|numeric|min:0',
            'categories' => 'nullable|array',
            'categories.*' => 'nullable|numeric|min:0',
        ]);

        $user = Auth::user();
        $family = $user?->currentFamily ?? Family::first();
        if (! $family) {
            return back()->with('error', '請先選擇或建立家庭！');
        }

        $periodType = $validated['period_type'] ?? 'month';

        if (! empty($validated['period_start']) && ! empty($validated['period_end'])) {
            $periodStart = Carbon::parse($validated['period_start'])->toDateString();
            $periodEnd = Carbon::parse($validated['period_end'])->toDateString();
        } else {
            $monthStr = $request->get('period_month') ?? $request->get('month') ?? now()->format('Y-m');
            $cDate = Carbon::createFromFormat('Y-m', $monthStr);
            $periodStart = $cDate->copy()->startOfMonth()->toDateString();
            $periodEnd = $cDate->copy()->endOfMonth()->toDateString();
        }

        $alertThresholds = $validated['alert_thresholds'] ?? [80, 100];
        $rollover = $request->boolean('rollover');

        // 若同期間已有主預算，則更新
        $mainBudget = Budget::where('family_id', $family->id)
            ->where('scope', 'family')
            ->whereNull('parent_budget_id')
            ->where('period_start', $periodStart)
            ->where('period_end', $periodEnd)
            ->first();

        if ($mainBudget) {
            $mainBudget->update([
                'period_type' => $periodType,
                'amount' => $validated['amount'],
                'alert_thresholds' => $alertThresholds,
                'rollover' => $rollover,
            ]);
        } else {
            $mainBudget = Budget::create([
                'family_id' => $family->id,
                'period_type' => $periodType,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'scope' => 'family',
                'scope_target_ids' => null,
                'amount' => $validated['amount'],
                'alert_thresholds' => $alertThresholds,
                'rollover' => $rollover,
                'parent_budget_id' => null,
            ]);
        }

        // 分類子預算設定 (支援 category_budgets 與 categories 兩種傳入格式)
        $categoryBudgets = $validated['category_budgets'] ?? $validated['categories'] ?? [];
        if (! empty($categoryBudgets)) {
            foreach ($categoryBudgets as $catId => $subAmount) {
                if ($subAmount !== null && $subAmount !== '' && (float) $subAmount >= 0) {
                    $catId = (int) $catId;
                    $subBudget = Budget::where('parent_budget_id', $mainBudget->id)
                        ->where('scope', 'category')
                        ->whereJsonContains('scope_target_ids', $catId)
                        ->first();

                    if ($subBudget) {
                        $subBudget->update([
                            'amount' => $subAmount,
                            'period_type' => $mainBudget->period_type,
                            'period_start' => $mainBudget->period_start,
                            'period_end' => $mainBudget->period_end,
                            'alert_thresholds' => $mainBudget->alert_thresholds,
                            'rollover' => $mainBudget->rollover,
                        ]);
                    } else {
                        Budget::create([
                            'family_id' => $family->id,
                            'period_type' => $mainBudget->period_type,
                            'period_start' => $mainBudget->period_start,
                            'period_end' => $mainBudget->period_end,
                            'scope' => 'category',
                            'scope_target_ids' => [$catId],
                            'amount' => $subAmount,
                            'alert_thresholds' => $mainBudget->alert_thresholds,
                            'rollover' => $mainBudget->rollover,
                            'parent_budget_id' => $mainBudget->id,
                        ]);
                    }
                }
            }
        }

        AuditService::log(
            'budget_created',
            Budget::class,
            $mainBudget->id,
            [
                '預算金額' => $mainBudget->amount,
                '開始日期' => $mainBudget->period_start?->toDateString(),
                '結束日期' => $mainBudget->period_end?->toDateString(),
            ],
            "建立預算 NT$ {$mainBudget->amount}",
            "成員 {$user->name} 設定了 {$mainBudget->period_start?->format('Y/m')} 預算 NT$ {$mainBudget->amount}"
        );

        $monthParam = Carbon::parse($periodStart)->format('Y-m');
        return redirect()->route('budgets.index', ['month' => $monthParam])->with('success', '預算已成功建立！');
    }

    /**
     * 檢視預算明細
     */
    public function show(Budget $budget)
    {
        $budget->load(['family', 'parentBudget', 'subBudgets']);
        return view('budgets.show', compact('budget'));
    }

    /**
     * 更新主預算與同步分類子預算
     */
    public function update(Request $request, Budget $budget)
    {
        $validated = $request->validate([
            'period_type' => 'nullable|in:month,quarter,year,custom',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
            'amount' => 'required|numeric|min:0',
            'alert_thresholds' => 'nullable|array',
            'rollover' => 'nullable|boolean',
            'category_budgets' => 'nullable|array',
            'category_budgets.*' => 'nullable|numeric|min:0',
            'categories' => 'nullable|array',
            'categories.*' => 'nullable|numeric|min:0',
        ]);

        $budget->update([
            'period_type' => $validated['period_type'] ?? $budget->period_type,
            'period_start' => $validated['period_start'] ?? $budget->period_start,
            'period_end' => $validated['period_end'] ?? $budget->period_end,
            'amount' => $validated['amount'],
            'alert_thresholds' => $validated['alert_thresholds'] ?? $budget->alert_thresholds,
            'rollover' => $request->has('rollover') ? $request->boolean('rollover') : $budget->rollover,
        ]);

        // 同步更新/新增/刪除底下分類子預算
        $categoryBudgets = $validated['category_budgets'] ?? $validated['categories'] ?? null;
        if ($categoryBudgets !== null) {
            $existingSubBudgets = $budget->subBudgets()->where('scope', 'category')->get();
            $handledIds = [];

            foreach ($categoryBudgets as $catId => $subAmount) {
                $catId = (int) $catId;
                if ($subAmount !== null && $subAmount !== '' && (float) $subAmount > 0) {
                    $subBudget = $existingSubBudgets->first(function ($sub) use ($catId) {
                        return is_array($sub->scope_target_ids) && in_array($catId, $sub->scope_target_ids);
                    });

                    if ($subBudget) {
                        $subBudget->update([
                            'amount' => $subAmount,
                            'period_type' => $budget->period_type,
                            'period_start' => $budget->period_start,
                            'period_end' => $budget->period_end,
                            'alert_thresholds' => $budget->alert_thresholds,
                            'rollover' => $budget->rollover,
                        ]);
                        $handledIds[] = $subBudget->id;
                    } else {
                        $newSub = Budget::create([
                            'family_id' => $budget->family_id,
                            'period_type' => $budget->period_type,
                            'period_start' => $budget->period_start,
                            'period_end' => $budget->period_end,
                            'scope' => 'category',
                            'scope_target_ids' => [$catId],
                            'amount' => $subAmount,
                            'alert_thresholds' => $budget->alert_thresholds,
                            'rollover' => $budget->rollover,
                            'parent_budget_id' => $budget->id,
                        ]);
                        $handledIds[] = $newSub->id;
                    }
                }
            }

            // 刪除未在清單中或設定為 0 的子預算
            $existingSubBudgets->whereNotIn('id', $handledIds)->each(fn ($sub) => $sub->delete());
        }

        AuditService::log(
            'budget_updated',
            Budget::class,
            $budget->id,
            ['預算金額' => $budget->amount],
            "更新預算 NT$ {$budget->amount}",
            "成員 " . Auth::user()->name . " 更新了預算設定"
        );

        $monthParam = $budget->period_start ? $budget->period_start->format('Y-m') : now()->format('Y-m');
        return redirect()->route('budgets.index', ['month' => $monthParam])->with('success', '預算設定已更新！');
    }

    /**
     * 刪除主預算與底下所有子預算
     */
    public function destroy(Budget $budget)
    {
        $budgetId = $budget->id;
        $budgetAmount = $budget->amount;

        $budget->subBudgets()->delete();
        $budget->delete();

        AuditService::log(
            'budget_deleted',
            Budget::class,
            $budgetId,
            ['預算金額' => $budgetAmount],
            "刪除預算",
            "成員 " . Auth::user()->name . " 刪除了預算設定"
        );

        return redirect()->route('budgets.index')->with('success', '預算已刪除。');
    }

    /**
     * 複製上一期預算設定至當前月份
     */
    public function copyPrevious(Request $request)
    {
        $user = Auth::user();
        $family = $user?->currentFamily ?? Family::first();
        if (! $family) {
            return back()->with('error', '請先選擇或建立家庭！');
        }

        $targetMonthStr = $request->input('target_month') ?? $request->input('month', now()->format('Y-m'));
        try {
            $targetMonth = Carbon::createFromFormat('Y-m', $targetMonthStr);
        } catch (\Exception $e) {
            $targetMonth = now();
        }

        $targetStart = $targetMonth->copy()->startOfMonth()->toDateString();
        $targetEnd = $targetMonth->copy()->endOfMonth()->toDateString();

        $prevMonth = $targetMonth->copy()->subMonth();
        $prevStart = $prevMonth->copy()->startOfMonth()->toDateString();
        $prevEnd = $prevMonth->copy()->endOfMonth()->toDateString();

        // 尋找上一期之主預算
        $prevBudget = Budget::with('subBudgets')
            ->where('scope', 'family')
            ->whereNull('parent_budget_id')
            ->whereDate('period_start', '<=', $prevEnd)
            ->whereDate('period_end', '>=', $prevStart)
            ->first();

        // 若無上個月預算，則抓取 targetStart 之前最近的一筆主預算
        if (! $prevBudget) {
            $prevBudget = Budget::with('subBudgets')
                ->where('scope', 'family')
                ->whereNull('parent_budget_id')
                ->whereDate('period_end', '<', $targetStart)
                ->orderByDesc('period_start')
                ->first();
        }

        if (! $prevBudget) {
            return redirect()->route('budgets.index', ['month' => $targetMonth->format('Y-m')])
                ->with('error', '找不到上一期的預算設定可供複製！');
        }

        // 建立或更新目標月份之主預算
        $targetBudget = Budget::where('family_id', $family->id)
            ->where('scope', 'family')
            ->whereNull('parent_budget_id')
            ->where('period_start', $targetStart)
            ->where('period_end', $targetEnd)
            ->first();

        if ($targetBudget) {
            $targetBudget->update([
                'amount' => $prevBudget->amount,
                'alert_thresholds' => $prevBudget->alert_thresholds,
                'rollover' => $prevBudget->rollover,
            ]);
        } else {
            $targetBudget = Budget::create([
                'family_id' => $family->id,
                'period_type' => 'month',
                'period_start' => $targetStart,
                'period_end' => $targetEnd,
                'scope' => 'family',
                'scope_target_ids' => null,
                'amount' => $prevBudget->amount,
                'alert_thresholds' => $prevBudget->alert_thresholds,
                'rollover' => $prevBudget->rollover,
                'parent_budget_id' => null,
            ]);
        }

        // 複製所有分類子預算
        $targetBudget->subBudgets()->delete();
        foreach ($prevBudget->subBudgets as $sub) {
            Budget::create([
                'family_id' => $family->id,
                'period_type' => 'month',
                'period_start' => $targetStart,
                'period_end' => $targetEnd,
                'scope' => $sub->scope,
                'scope_target_ids' => $sub->scope_target_ids,
                'amount' => $sub->amount,
                'alert_thresholds' => $sub->alert_thresholds,
                'rollover' => $sub->rollover,
                'parent_budget_id' => $targetBudget->id,
            ]);
        }

        AuditService::log(
            'budget_copied',
            Budget::class,
            $targetBudget->id,
            ['來源預算ID' => $prevBudget->id],
            "複製預算設定",
            "成員 {$user->name} 複製了上一期預算設定至 {$targetMonth->format('Y-m')}"
        );

        return redirect()->route('budgets.index', ['month' => $targetMonth->format('Y-m')])
            ->with('success', '已成功複製上一期預算設定！');
    }
}

