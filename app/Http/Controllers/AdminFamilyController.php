<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminFamilyController extends Controller
{
    protected function checkAdmin()
    {
        if (! auth()->check() || ! auth()->user()->is_system_admin) {
            abort(403, '存取拒絕：權限不足，只有最高系統管理員可以訪問管理介面。');
        }
    }

    public function index()
    {
        $this->checkAdmin();
        $families = Family::paginate(15);
        return view('admin.families.index', compact('families'));
    }

    public function create()
    {
        $this->checkAdmin();
        return view('admin.families.create');
    }

    public function store(Request $request)
    {
        $this->checkAdmin();

        $admin = auth()->user();

        // 安全性：管理員本身必須 isAdmin() 為 true 才能建立家庭。
        // 規則允許 admin 建立「多個」家庭（無 ownedFamilies 數量限制）。
        if (! $admin->isAdmin()) {
            abort(403, '僅有系統管理員可建立家庭。');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $family = Family::create([
            'name' => $validated['name'],
            'currency' => 'TWD',
            'invite_code' => strtoupper(Str::random(6)),
            'total_pool_amount' => 0,
            'created_by_user_id' => $admin->id,
            'owner_user_id' => $admin->id,
        ]);

        // 把當前管理員自動加入這個家庭，樞紐角色 = admin（這樣在 family_user.role
        // 裡仍保有管理員身份，未來若開放「家庭內管理員」概念可直接使用）。
        if (! $family->members()->where('users.id', $admin->id)->exists()) {
            $family->members()->attach($admin->id, ['role' => 'admin', 'is_active' => true]);
        }

        // 預設帳戶
        \App\Models\Account::create([
            'family_id' => $family->id,
            'name' => '日常現金',
            'type' => 'cash',
            'balance' => 0.00,
            'currency' => 'TWD',
            'color' => '#10B981',
            'icon' => 'heroicon-o-banknotes',
        ]);

        $admin->update(['current_family_id' => $family->id]);

        return redirect()->route('admin.families.index')->with('success', '家庭已成功建立！預設金額池為 NT$ 0，邀請碼：' . $family->invite_code);
    }

    public function edit(Family $family)
    {
        $this->checkAdmin();
        return view('admin.families.edit', compact('family'));
    }

    public function update(Request $request, Family $family)
    {
        $this->checkAdmin();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $family->update($validated);
        return redirect()->route('admin.families.index')->with('success', 'Family updated.');
    }

    public function destroy(Family $family)
    {
        $this->checkAdmin();

        // Reset current_family_id for all users pointing to this family
        User::where('current_family_id', $family->id)
            ->update(['current_family_id' => null]);

        // Delete notifications tied to this family
        \App\Models\Notification::where('family_id', $family->id)->delete();

        // Detach all users from the family pivot
        $family->members()->detach();

        // Delete related records that have family_id FK
        $family->accounts()->delete();
        $family->transactions()->delete();

        $family->delete();
        return redirect()->route('admin.families.index')->with('success', '家庭已成功刪除，所有關聯資料已清除。');
    }

    /**
     * 管理員即時預覽家庭 AI 財務狀態簡易分析 API
     */
    public function aiSummary(Family $family)
    {
        $this->checkAdmin();
        $month = Carbon::now()->format('Y-m');
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $monthlyIncome = (float) \App\Models\Transaction::where('family_id', $family->id)
            ->where('type', 'income')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyExpense = (float) \App\Models\Transaction::where('family_id', $family->id)
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $subscriptions = \App\Models\Subscription::where('family_id', $family->id)
            ->where('is_paused', false)
            ->get();

        $metrics = [
            'family_name' => $family->name,
            'month' => $month,
            'total_income' => $monthlyIncome,
            'total_expense' => $monthlyExpense,
            'net_balance' => $monthlyIncome - $monthlyExpense,
            'savings_rate' => $monthlyIncome > 0 ? max(0, round((($monthlyIncome - $monthlyExpense) / $monthlyIncome) * 100, 1)) : 0,
            'subscription_count' => $subscriptions->count(),
            'subscription_total' => (float) $subscriptions->sum('amount'),
            'subscriptions' => $subscriptions->map(fn($s) => ['name' => $s->name, 'amount' => (float)$s->amount])->toArray(),
            'top_categories' => [],
            'budget_alerts' => [],
        ];

        try {
            $analysis = \App\Services\Ai\GeminiService::analyzeFamilyFinanceSummary($metrics);
            return response()->json([
                'success' => true,
                'metrics' => $metrics,
                'analysis' => $analysis,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'metrics' => $metrics,
                'analysis' => '【AI 提醒】目前無法取得 Gemini API 即時分析（' . $e->getMessage() . '），請至 AI 智能設定確認 Token。',
                'message' => $e->getMessage(),
            ]);
        }
    }
}
