<?php

namespace App\Http\Controllers;

use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FamilyController extends Controller
{
    /**
     * 切換目前檢視的家庭。
     *
     * 規則：
     *   - 一般使用者：必須已是該家庭的成員 (family_user 中存在) 才能切換。
     *   - 系統管理員：可以切換到「任何」家庭 (管理員可在後台總覽每個家庭)，
     *     但不會自動加入家庭 — 若要編輯家庭資料，需透過家庭家長的邀請連結。
     */
    public function switch(Request $request)
    {
        $validated = $request->validate([
            'family_id' => 'required|exists:families,id',
        ]);

        $user = Auth::user();
        $familyId = $validated['family_id'];

        if ($user->is_system_admin) {
            // 管理員切換家庭：
            //   - 不自動加入 family_user (不會被視為家庭成員)
            //   - 不自動設為 current_family_id 的擁有者
            //   - 仍可在「唯讀模式」檢視該家庭資料 (透過 FamilyScope 過濾)
            //   - 若要編輯，需由家庭家長發邀請，並透過 /invitation/accept/{token} 接受
        } else {
            // 一般使用者：必須是該家庭的成員才能切換
            if (! $user->families()->where('families.id', $familyId)->exists()) {
                return back()->with('error', '您無權存取該家庭帳務資料。');
            }
        }

        $user->update(['current_family_id' => $familyId]);

        $msg = $user->is_system_admin && ! $user->canEditCurrentFamily()
            ? '已切換至該家庭 (管理員唯讀模式)。若要編輯資料，請聯絡該家庭家長發送邀請。'
            : '已成功切換目前家庭！';

        return back()->with('success', $msg);
    }
}
