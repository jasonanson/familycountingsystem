<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    /**
     * 查詢當前家庭與系統預設之主/子分類選單，區分「支出」與「收入」類別，計算各分類之關聯交易筆數。
     */
    public function index()
    {
        if (! Auth::check()) {
            $defaultUser = \App\Models\User::where('account', 'parent')->first() ?? \App\Models\User::first();
            if ($defaultUser) {
                Auth::login($defaultUser);
            }
        }

        $famId = Auth::user()?->current_family_id ?? 0;
        // FamilyScope 會自動過濾當前家庭 + 系統預設 NULL
        $allCategories = \Illuminate\Support\Facades\Cache::remember("categories_tree_family_{$famId}", 3600, function () {
            $cats = Category::with(['children' => function ($q) {
                $q->withCount('transactions')->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
            }])
            ->withCount('transactions')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

            // 計算各主分類包含子分類的總交易筆數
            $cats->each(function ($category) {
                $childrenTxCount = $category->children->sum('transactions_count');
                $category->total_transactions_count = $category->transactions_count + $childrenTxCount;
            });

            return $cats;
        });

        // 主分類 (parent_id 為 null)
        $parentCategories = $allCategories->whereNull('parent_id');

        // 區分「支出」與「收入」類別
        $expenseCategories = $parentCategories->filter(fn($c) => in_array($c->type, ['expense', 'both']));
        $incomeCategories  = $parentCategories->filter(fn($c) => in_array($c->type, ['income', 'both']));

        return view('categories.index', compact(
            'allCategories',
            'parentCategories',
            'expenseCategories',
            'incomeCategories'
        ));
    }

    /**
     * 家長新增分類 (包含 name, type: expense/income/both, parent_id, icon, color, scope: family/personal)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:expense,income,both',
            'parent_id' => 'nullable|exists:categories,id',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'scope' => 'nullable|in:family,personal',
        ]);

        $user = Auth::user();
        $family = $user?->currentFamily;

        $category = Category::create([
            'family_id' => $family?->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?: 'category',
            'color' => $validated['color'] ?: '#006b5f',
            'sort_order' => 0,
            'is_custom' => true,
            'scope' => $validated['scope'] ?? 'family',
            'type' => $validated['type'],
            'is_archived' => false,
        ]);

        AuditService::log(
            'category_created',
            Category::class,
            $category->id,
            [
                '分類名稱' => $category->name,
                '類型' => $category->type,
                '父分類ID' => $category->parent_id,
            ],
            "新增分類 {$category->name}",
            "成員 {$user->name} 新增了分類「{$category->name}」"
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'category' => $category]);
        }

        return redirect()->route('categories.index')->with('success', "🎉 成功新增分類「{$category->name}」！");
    }

    /**
     * 修改分類名稱、圖示、色彩與父分類
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|in:expense,income,both',
            'parent_id' => 'nullable|exists:categories,id',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'scope' => 'nullable|in:family,personal',
        ]);

        // 防呆：禁止設定自己為父分類
        if (isset($validated['parent_id']) && (int)$validated['parent_id'] === (int)$category->id) {
            return back()->with('error', '⚠️ 無法設定分類為自己的父分類！');
        }

        $user = Auth::user();

        $category->update([
            'name' => $validated['name'],
            'type' => $validated['type'] ?? $category->type,
            'parent_id' => $validated['parent_id'] ?? null,
            'icon' => $validated['icon'] ?: $category->icon,
            'color' => $validated['color'] ?: $category->color,
            'scope' => $validated['scope'] ?? $category->scope,
        ]);

        AuditService::log(
            'category_updated',
            Category::class,
            $category->id,
            [
                '分類名稱' => $category->name,
                '類型' => $category->type,
            ],
            "更新分類 {$category->name}",
            "成員 {$user->name} 更新了分類「{$category->name}」資訊"
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'category' => $category]);
        }

        return redirect()->route('categories.index')->with('success', "🎉 分類「{$category->name}」已成功更新！");
    }

    /**
     * 刪除自訂分類 (若已有關聯交易則保護無法直接刪除)
     */
    public function destroy(Category $category)
    {
        // 1. 檢查自身是否有關聯交易
        $hasTransactions = Transaction::where('category_id', $category->id)->exists();

        // 2. 檢查其所有子分類是否有關聯交易
        $childrenIds = $category->children()->pluck('id')->toArray();
        if (! empty($childrenIds)) {
            $hasChildrenTransactions = Transaction::whereIn('category_id', $childrenIds)->exists();
            if ($hasChildrenTransactions) {
                $hasTransactions = true;
            }
        }

        if ($hasTransactions) {
            return back()->with('error', "⚠️ 分類「{$category->name}」已有關聯交易紀錄，系統為保護財務數據安全，無法直接刪除！");
        }

        // 保護系統預設分類
        if (! $category->is_custom && is_null($category->family_id)) {
            return back()->with('error', "⚠️ 系統預設分類「{$category->name}」無法刪除！");
        }

        $categoryName = $category->name;
        $categoryId = $category->id;
        $category->delete();

        AuditService::log(
            'category_deleted',
            Category::class,
            $categoryId,
            ['分類名稱' => $categoryName],
            "刪除分類 {$categoryName}",
            "成員 " . Auth::user()->name . " 刪除了自訂分類「{$categoryName}」"
        );

        return redirect()->route('categories.index')->with('success', "🎉 自訂分類「{$categoryName}」已成功刪除！");
    }
}
