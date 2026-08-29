<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Family;
use App\Models\Transaction;
use App\Services\AuditService;
use Illuminate\Http\Request;

class AdminCategoryController extends Controller
{
    protected function checkAdmin()
    {
        if (! auth()->check() || ! auth()->user()->is_system_admin) {
            abort(403, '存取拒絕：權限不足，只有最高系統管理員可以訪問管理介面。');
        }
    }

    /**
     * 全站分類總覽（管理員限定）
     * 列出所有家庭的所有分類，包含系統預設（family_id=null）和自訂分類。
     */
    public function index(Request $request)
    {
        $this->checkAdmin();

        $search = trim((string) $request->get('search', ''));
        $familyFilter = $request->get('family_id');
        $typeFilter = $request->get('type');

        $query = Category::withoutGlobalScopes()
            ->with(['family', 'parent'])
            ->withCount('transactions');

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($familyFilter === 'system') {
            $query->whereNull('family_id');
        } elseif ($familyFilter === 'custom') {
            $query->whereNotNull('family_id');
        } elseif (!empty($familyFilter) && $familyFilter !== 'all') {
            $query->where('family_id', $familyFilter);
        }

        if (in_array($typeFilter, ['expense', 'income', 'both'], true)) {
            $query->where('type', $typeFilter);
        }

        $categories = $query
            ->orderByRaw('family_id IS NULL DESC')   // 系統預設排前面
            ->orderBy('family_id', 'asc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(20)
            ->withQueryString();

        $allFamilies = Family::orderBy('name')->get();

        // 統計
        $stats = [
            'total' => Category::withoutGlobalScopes()->count(),
            'system' => Category::withoutGlobalScopes()->whereNull('family_id')->count(),
            'custom' => Category::withoutGlobalScopes()->whereNotNull('family_id')->count(),
            'archived' => Category::withoutGlobalScopes()->where('is_archived', true)->count(),
        ];

        return view('admin.categories.index', compact(
            'categories', 'allFamilies', 'search', 'familyFilter', 'typeFilter', 'stats'
        ));
    }

    /**
     * 管理員強制刪除分類（繞過家庭限制，但仍保護已有交易的分類）
     */
    public function destroy(Request $request, Category $category)
    {
        $this->checkAdmin();

        // 取消 FamilyScope 才能找到
        $category = Category::withoutGlobalScopes()->findOrFail($category->id);

        $hasTransactions = Transaction::where('category_id', $category->id)->exists();
        $childrenIds = $category->children()->pluck('id')->toArray();
        if (! empty($childrenIds)) {
            $hasChildrenTransactions = Transaction::whereIn('category_id', $childrenIds)->exists();
            if ($hasChildrenTransactions) {
                $hasTransactions = true;
            }
        }

        if ($hasTransactions) {
            return back()->with('error', "⚠️ 分類「{$category->name}」已有關聯交易紀錄，無法刪除（保護財務數據）。");
        }

        $categoryName = $category->name;
        $categoryId = $category->id;
        $category->delete();

        AuditService::log(
            'admin_category_deleted',
            Category::class,
            $categoryId,
            ['分類名稱' => $categoryName, '管理員' => auth()->user()->name],
            "管理員刪除分類 {$categoryName}",
            "管理員 " . auth()->user()->name . " 強制刪除了分類「{$categoryName}」"
        );

        return redirect()->route('admin.categories.index')->with('success', "✅ 已刪除分類「{$categoryName}」");
    }
}
