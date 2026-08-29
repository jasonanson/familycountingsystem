<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class FamilyScope implements Scope
{
    /**
     * 套用 FamilyScope：
     *
     *   未登入 → 全部資料都看不到 (where 0=1)
     *
     *   系統管理員：
     *     - 若 current_family_id 有設定 → 僅看該家庭 (管理員切換家庭後的唯讀模式)
     *     - 若 current_family_id 為 null → 看全域 (後台總覽)
     *
     *   一般使用者：
     *     - 沒 current_family_id → 看不到 (where 0=1)
     *     - 有 current_family_id → 僅看該家庭
     *
     *   Model 沒 family_id 欄位就跳過 (User, Notification, AuditLog 等)
     *
     *   Category 特殊處理：家庭自訂 + 全域預設 (NULL)
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! Auth::check()) {
            $builder->whereRaw('0 = 1');
            return;
        }

        $user = Auth::user();

        if ($user->is_system_admin) {
            // 管理員：若 current_family_id 有指定 → 只看該家庭 (切換檢視)
            // 若 current_family_id 為 null → 看全域 (後台總覽)
            if (! $user->current_family_id) {
                return; // 全域檢視，不加過濾
            }
            // 往下走，照一般方式過濾 family_id
        } else {
            // 一般使用者：沒 current_family_id 看不到
            if (! $user->current_family_id) {
                $builder->whereRaw('0 = 1');
                return;
            }
        }

        // Model 沒 family_id 欄位就跳過 (User, Notification, AuditLog 等)
        if (! Schema::hasColumn($model->getTable(), 'family_id')) {
            return;
        }

        // Category 特殊:家庭 + 全域預設(NULL)
        if (get_class($model) === \App\Models\Category::class) {
            $builder->where(function (Builder $query) use ($user, $model) {
                $query->where($model->getTable() . '.family_id', $user->current_family_id)
                      ->orWhereNull($model->getTable() . '.family_id');
            });
            return;
        }

        // 一般:過濾 current_family_id
        $builder->where($model->getTable() . '.family_id', $user->current_family_id);
    }
}
