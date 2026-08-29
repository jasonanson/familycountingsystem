<?php

namespace App\Http\Controllers;

use App\Models\FamilyInvitation;
use App\Models\User;
use App\Mail\FamilyInvitationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FamilyInvitationController extends Controller
{
    /**
     * List all invitations for current family.
     */
    public function index()
    {
        $user = Auth::user();

        // 系統管理員 (含 admin 唯讀檢視者) 可以查看邀請列表，但不可新增/取消
        // 只有 parent 角色可以建立新邀請 (store/resend/cancel)
        $family = $user->currentFamily;
        if (!$family) {
            return response()->json(['error' => '請先選擇家庭。'], 400);
        }

        $invitations = FamilyInvitation::where('family_id', $family->id)
            ->whereNull('used_at')
            ->latest()
            ->get();

        return response()->json($invitations);
    }

    /**
     * Create a new invitation and send email.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->isParentInCurrentFamily()) {
            abort(403, '只有家長才能發送邀請。');
        }

        $family = $user->currentFamily;
        if (!$family) {
            return back()->withErrors(['error' => '請先選擇家庭。']);
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

        // 智慧判斷角色：
        //   - 若該 email 已是「系統管理員」，強制以 parent 邀請 (讓 admin 可編輯此家庭)
        //   - 否則依使用者選擇 (預設 child)
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

        $inviteUrl = url('/invitation/accept/' . $token);

        try {
            Mail::to($validated['email'])
                ->send(new FamilyInvitationMail($invitation, $inviteUrl));
        } catch (\Exception $e) {
            Log::warning('Failed to send invitation email: ' . $e->getMessage());
            return back()->with('success', '邀請已建立，但電子郵件發送失敗。請手動複製邀請連結。')
                         ->with('invited_url', $inviteUrl);
        }

        return back()->with('success', '邀請已成功發送至 ' . $validated['email']);
    }

    /**
     * Resend the invitation email.
     */
    public function resend(FamilyInvitation $invitation)
    {
        $user = Auth::user();
        if (!$user->isParentInCurrentFamily() || $invitation->family_id !== $user->current_family_id) {
            abort(403, '無權限重新發送此邀請。');
        }

        if ($invitation->used_at) {
            return back()->withErrors(['error' => '此邀請已被使用。']);
        }

        // Refresh token and expiry
        $invitation->update([
            'token' => Str::random(32),
            'expires_at' => now()->addDays(7),
        ]);

        $inviteUrl = url('/invitation/accept/' . $invitation->token);

        try {
            Mail::to($invitation->email)
                ->send(new FamilyInvitationMail($invitation, $inviteUrl));
        } catch (\Exception $e) {
            Log::warning('Failed to resend invitation email: ' . $e->getMessage());
            return back()->withErrors(['error' => '電子郵件發送失敗，請稍後再試。']);
        }

        return back()->with('success', '邀請已重新發送至 ' . $invitation->email);
    }

    /**
     * Cancel an invitation.
     */
    public function cancel(FamilyInvitation $invitation)
    {
        $user = Auth::user();
        if (!$user->isParentInCurrentFamily() || $invitation->family_id !== $user->current_family_id) {
            abort(403, '無權限取消此邀請。');
        }

        if ($invitation->used_at) {
            return back()->withErrors(['error' => '此邀請已被使用，無法取消。']);
        }

        $invitation->update(['used_at' => now()]); // Soft-cancel by setting used_at

        return back()->with('success', '邀請已取消。');
    }

    /**
     * Accept an invitation (Public Route).
     */
    public function accept($token)
    {
        $invitation = FamilyInvitation::where('token', $token)->first();

        if (!$invitation) {
            return redirect('/')->withErrors(['error' => '無效的邀請連結。']);
        }

        if ($invitation->used_at) {
            return redirect('/')->withErrors(['error' => '此邀請連結已被使用或取消。']);
        }

        if (now()->gt($invitation->expires_at)) {
            return redirect('/')->withErrors(['error' => '此邀請連結已過期。']);
        }

        if (Auth::check()) {
            $user = Auth::user();
            
            // Attach user to family
            if (!$invitation->family->members->contains($user->id)) {
                // 系統管理員接受邀請：依邀請的 role 為準（通常為 'parent'）。
                // 一旦加入家庭，User::canEditCurrentFamily() 就會通過，管理員即可編輯此家庭資料。
                $invitation->family->members()->attach($user->id, [
                    'role' => $invitation->role,
                    'is_active' => true,
                    'joined_at' => now(),
                ]);
            }

            // Update user's current family
            $user->update(['current_family_id' => $invitation->family_id]);

            // Mark invitation as used
            $invitation->update(['used_at' => now()]);

            // === 自動建立預設帳戶：管理員剛加入家庭時沒有帳戶，補一個 cash 帳戶方便記帳 ===
            try {
                if (!\App\Models\Account::withoutGlobalScopes()
                    ->where('family_id', $invitation->family_id)
                    ->where('name', '日常現金')
                    ->exists()) {
                    \App\Models\Account::create([
                        'family_id' => $invitation->family_id,
                        'name' => '日常現金',
                        'type' => 'cash',
                        'balance' => 0,
                        'currency' => 'TWD',
                        'color' => '#10B981',
                        'icon' => 'heroicon-o-banknotes',
                    ]);
                }
            } catch (\Throwable $e) {
                // 帳戶已存在則略過
            }

            $msg = $user->is_system_admin
                ? '您已成功以家長身份加入「' . $invitation->family->name . '」！現在可以編輯此家庭資料。'
                : '您已成功加入「' . $invitation->family->name . '」！';

            return redirect()->route('dashboard')->with('success', $msg);
        }

        // Redirect to register page with token if not logged in
        return redirect()->route('register', ['token' => $token]);
    }
}
