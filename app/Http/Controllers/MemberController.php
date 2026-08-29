<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\FamilyUser;
use App\Models\FamilyInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MemberController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $family = $user->currentFamily ?: $user->families()->first();
        $isParent = $user->isParentInCurrentFamily();

        $members = $family 
            ? $family->members()->withPivot(['role', 'is_active', 'display_name', 'joined_at'])->get() 
            : collect();

        $invitations = ($family && $isParent)
            ? FamilyInvitation::where('family_id', $family->id)
                ->whereNull('used_at')
                ->latest()
                ->get()
            : collect();

        return view('members.index', compact('members', 'family', 'isParent', 'invitations'));
    }

    /**
     * Flow A: 家長在後台直接建立孩童帳號
     */
    public function storeChildDirect(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isParentInCurrentFamily()) {
            abort(403, '只有家長才能直接建立孩童帳號。');
        }

        $family = $user->currentFamily ?: $user->families()->first();
        if (! $family) {
            return back()->withErrors(['error' => '請先選擇或建立家庭。']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account' => 'required|string|min:3|max:30|regex:/^[A-Za-z0-9_]+$/|unique:users,account',
            'password' => 'required|string|min:6',
        ], [
            'name.required' => '請輸入孩童姓名',
            'account.required' => '請輸入登入帳號',
            'account.unique' => '這個帳號已經被使用，請更換一個',
            'account.regex' => '帳號只能包含英文字母、數字和底線',
            'password.required' => '請設定密碼',
            'password.min' => '密碼至少需要 6 個字元',
        ]);

        $placeholderEmail = $validated['account'] . '@family.local';
        if (User::where('email', $placeholderEmail)->exists()) {
            $placeholderEmail = $validated['account'] . '_' . Str::random(4) . '@family.local';
        }

        $child = User::create([
            'name' => $validated['name'],
            'account' => $validated['account'],
            'email' => $placeholderEmail,
            'password' => Hash::make($validated['password']),
            'registration_role' => 'member',
        ]);

        $family->members()->attach($child->id, [
            'role' => 'child',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $child->update(['current_family_id' => $family->id]);

        return redirect()->route('members.index')->with('success', "🎉 已成功建立孩童帳號「{$child->name}」！");
    }

    /**
     * Flow B: 家長輸入孩童 Email 產生邀請連結
     */
    public function inviteChild(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isParentInCurrentFamily()) {
            abort(403, '只有家長才能產生孩童邀請連結。');
        }

        $family = $user->currentFamily ?: $user->families()->first();
        if (! $family) {
            return back()->withErrors(['error' => '請先選擇或建立家庭。']);
        }

        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'nullable|in:parent,child',
        ], [
            'email.required' => '請輸入 Email',
            'email.email' => 'Email 格式不正確',
            'role.in' => '邀請角色僅支援 parent / child',
        ]);

        $token = Str::random(32);

        // 智慧判斷角色：若 email 是系統管理員，自動以 parent 邀請
        $invitee = User::where('email', $validated['email'])->first();
        $role = $validated['role'] ?? null;
        if ($invitee && $invitee->is_system_admin) {
            $role = 'parent';
        } else {
            $role = $role ?: 'child';
        }

        $invitation = FamilyInvitation::create([
            'family_id' => $family->id,
            'email' => $validated['email'],
            'token' => $token,
            'role' => $role,
            'inviter_user_id' => $user->id,
            'expires_at' => now()->addDays(7),
        ]);

        $inviteUrl = url('/join-family?token=' . $token);
        $registerUrl = url('/register?token=' . $token);

        // Send invitation email
        try {
            \Illuminate\Support\Facades\Mail::to($validated['email'])
                ->send(new \App\Mail\FamilyInvitationMail($invitation, $registerUrl));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send invitation email: ' . $e->getMessage());
        }

        return redirect()->route('members.index')
            ->with('invited_url', $registerUrl)
            ->with('success', '🎉 已成功建立孩童邀請連結！您可以複製下方連結提供給孩童使用。');
    }

    /**
     * 切換成員啟用/停用狀態
     */
    public function toggleStatus(Request $request, $id)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isParentInCurrentFamily()) {
            abort(403, '只有家長才能變更成員狀態。');
        }

        $family = $user->currentFamily ?: $user->families()->first();
        if (! $family) {
            return back()->withErrors(['error' => '找不到對應家庭']);
        }

        $pivot = FamilyUser::where('family_id', $family->id)
            ->where('user_id', $id)
            ->firstOrFail();

        $pivot->update([
            'is_active' => ! $pivot->is_active,
        ]);

        $statusText = $pivot->is_active ? '啟用' : '停用';
        return redirect()->route('members.index')->with('success', "成員狀態已切換為「{$statusText}」。");
    }

    /**
     * 將成員自當前 Family 移除
     */
    public function destroy(Request $request, $id)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isParentInCurrentFamily()) {
            abort(403, '只有家長才能移除成員。');
        }

        if ((int) $id === (int) $user->id) {
            return back()->withErrors(['error' => '您無法將自己從家庭中移除。']);
        }

        $family = $user->currentFamily ?: $user->families()->first();
        if (! $family) {
            return back()->withErrors(['error' => '找不到對應家庭']);
        }

        $targetUser = User::find($id);
        $family->members()->detach($id);

        if ($targetUser && $targetUser->current_family_id === $family->id) {
            $nextFamily = $targetUser->families()->first();
            $targetUser->update(['current_family_id' => $nextFamily?->id]);
        }

        return redirect()->route('members.index')->with('success', '已將成員移除出家庭。');
    }
}
