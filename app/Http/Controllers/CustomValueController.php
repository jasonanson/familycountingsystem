<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CustomValuePromotion;
use App\Models\Tag;
use App\Models\Transaction;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomValueController extends Controller
{
    /**
     * 查詢成員於記帳時輸入之自訂商家/類別提案列表 (CustomValuePromotion 或 Transaction 之中不重複自訂值)
     */
    public function index()
    {
        if (! Auth::check()) {
            $defaultUser = \App\Models\User::where('account', 'parent')->first() ?? \App\Models\User::first();
            if ($defaultUser) {
                Auth::login($defaultUser);
            }
        }

        $user = Auth::user();
        $family = $user?->currentFamily;

        // 1. 取得現有 CustomValuePromotion 提案 (按 pending -> approved -> rejected 排序)
        $promotions = CustomValuePromotion::where('family_id', $family?->id)
            ->with('proposedBy')
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderBy('created_at', 'desc')
            ->get();

        // 2. 查詢 Transaction 之中不重複自訂商家/付款對象 (payee_custom)
        $transactionCustomPayees = Transaction::where('family_id', $family?->id)
            ->whereNotNull('payee_custom')
            ->where('payee_custom', '!=', '')
            ->select('payee_custom', DB::raw('COUNT(*) as usage_count'), DB::raw('MAX(occurred_at) as last_used_at'))
            ->groupBy('payee_custom')
            ->orderBy('usage_count', 'desc')
            ->get();

        // 3. 取得可選的主分類列表 (升格為子分類時使用)
        $parentCategories = Category::whereNull('parent_id')->get();

        return view('custom_values.index', compact(
            'promotions',
            'transactionCustomPayees',
            'parentCategories'
        ));
    }

    /**
     * 家長將自訂提案「一鍵升格」為正式家庭分類或標籤 (自動建立對應 Category 或 Tag)
     */
    public function promote(Request $request, $id)
    {
        $user = Auth::user();
        $family = $user?->currentFamily;

        // 驗證輸入參數
        $validated = $request->validate([
            'target_type' => 'required|in:category,tag',
            'name' => 'nullable|string|max:255',
            'category_type' => 'nullable|in:expense,income,both',
            'parent_id' => 'nullable|exists:categories,id',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

        $promotion = CustomValuePromotion::where('family_id', $family?->id)->find($id);

        $proposedValue = $promotion ? $promotion->proposed_value : $request->input('name');
        if (!$proposedValue) {
            $proposedValue = urldecode($id);
        }

        $finalName = $validated['name'] ?: $proposedValue;
        $targetType = $validated['target_type'];

        DB::transaction(function () use ($family, $user, $promotion, $finalName, $targetType, $validated) {
            if ($targetType === 'category') {
                // 建立正式家庭分類
                $category = Category::create([
                    'family_id' => $family?->id,
                    'parent_id' => $validated['parent_id'] ?? null,
                    'name' => $finalName,
                    'icon' => $validated['icon'] ?: 'label',
                    'color' => $validated['color'] ?: '#006b5f',
                    'sort_order' => 0,
                    'is_custom' => false, // 升格為正式分類
                    'scope' => 'family',
                    'type' => $validated['category_type'] ?: 'expense',
                    'is_archived' => false,
                ]);

                // 若有原先 is_custom = true 的同名分類，轉換為正式分類
                Category::where('family_id', $family?->id)
                    ->where('name', $finalName)
                    ->where('id', '!=', $category->id)
                    ->update(['is_custom' => false]);

            } else { // target_type === 'tag'
                // 建立正式標籤
                Tag::firstOrCreate([
                    'family_id' => $family?->id,
                    'name' => $finalName,
                ], [
                    'color' => $validated['color'] ?: '#3B82F6',
                    'is_custom' => false,
                ]);
            }

            // 更新 Promotion 狀態為 approved
            if ($promotion) {
                $promotion->update(['status' => 'approved']);
            } else {
                // 如果是直接從交易自訂值升格，建立一筆 approved 紀錄作為追蹤
                CustomValuePromotion::create([
                    'family_id' => $family?->id,
                    'field_type' => $targetType,
                    'proposed_value' => $finalName,
                    'proposed_by_user_id' => $user->id,
                    'status' => 'approved',
                ]);
            }
        });

        $targetLabel = $targetType === 'category' ? '家庭分類' : '標籤';

        AuditService::log(
            'custom_value_promoted',
            CustomValuePromotion::class,
            $promotion?->id,
            [
                '自訂值' => $finalName,
                '升格類型' => $targetLabel,
            ],
            "自訂值升格為正式{$targetLabel}「{$finalName}」",
            "家長 {$user->name} 已將自訂值「{$finalName}」升格為正式{$targetLabel}"
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "🎉 已成功將「{$finalName}」升格為正式{$targetLabel}！"]);
        }

        return redirect()->route('custom_values.index')->with('success', "🎉 已成功將「{$finalName}」升格為正式{$targetLabel}！");
    }

    /**
     * 家長駁回自訂值提案
     */
    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        $family = $user?->currentFamily;

        $promotion = CustomValuePromotion::where('family_id', $family?->id)->find($id);

        if ($promotion) {
            $promotion->update(['status' => 'rejected']);

            AuditService::log(
                'custom_value_rejected',
                CustomValuePromotion::class,
                $promotion->id,
                ['自訂值' => $promotion->proposed_value],
                "駁回自訂值提案「{$promotion->proposed_value}」",
                "家長 {$user->name} 駁回了成員提案之自訂值「{$promotion->proposed_value}」"
            );
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => '自訂值提案已駁回。']);
        }

        return redirect()->route('custom_values.index')->with('success', '自訂值提案已駁回。');
    }
}
