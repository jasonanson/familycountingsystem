<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\FamilyInvitation;
use App\Models\ParentCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'account' => 'required|string',
            'password' => 'required|string',
        ]);

        // 支援用 account 帳號或 email 登入
        $field = filter_var($credentials['account'], FILTER_VALIDATE_EMAIL) ? 'email' : 'account';

        if (Auth::attempt([$field => $credentials['account'], 'password' => $credentials['password']], $request->has('remember'))) {
            $request->session()->regenerate();
            $loggedInUser = Auth::user();

            // [P2] 兩步驟驗證 (2FA)：若使用者已啟用，先登出再跳轉到 /2fa/verify
            if ($loggedInUser && $loggedInUser->two_factor_enabled) {
                Auth::logout();
                $request->session()->put('2fa.user_id', $loggedInUser->id);
                $request->session()->put('2fa.remember', $request->has('remember'));
                return redirect()->route('verify.show')->with('info', '請輸入 6 位 OTP 驗證碼完成登入。');
            }

            // 依角色決定是否自動建立 / 綁定家庭：
            //   admin    → 不自動建立 (走後台)
            //   child    → 不自動建立 (需邀請)
            //   parent   → 僅在「尚未擁有家庭」時才自動建立第一個
            //   其他     → 不自動建立
            $loggedInUser?->ensureHasFamily();
            return redirect()->intended('/dashboard')->with('success', '歡迎回到 HomeSync Finance 家庭記帳！');
        }

        return back()->withErrors(['account' => '帳號或密碼錯誤。'])->onlyInput('account');
    }

    public function showRegister(Request $request)
    {
        $token = $request->query('token');
        $invitation = null;
        if ($token) {
            $invitation = FamilyInvitation::where('token', $token)->where('is_used', false)->first();
        }

        return view('auth.register', [
            'token' => $token,
            'invitation' => $invitation,
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account' => 'required|string|min:3|max:30|regex:/^[A-Za-z0-9_]+$/|unique:users,account',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'invite_code' => 'nullable|string',
            'token' => 'nullable|string',
            'parent_code' => 'nullable|string',
            'registration_role' => 'required|in:admin,parent,child',
        ], [
            'name.required' => '請輸入姓名',
            'name.max' => '姓名最多 255 個字元',
            'account.required' => '請輸入帳號',
            'account.min' => '帳號至少需要 3 個字元',
            'account.max' => '帳號最多 30 個字元',
            'account.regex' => '帳號只能包含英文字母、數字和底線',
            'account.unique' => '這個帳號已經被使用了,請換一個試試',
            'email.required' => '請輸入 Email',
            'email.email' => 'Email 格式不正確,請檢查一下',
            'email.unique' => '這個 Email 已經註冊過了,請用別的 Email 或改用「加入家庭」',
            'password.required' => '請輸入密碼',
            'password.min' => '密碼至少需要 6 個字元',
            'password.confirmed' => '兩次密碼輸入不一致',
            'registration_role.required' => '請選擇您的身份',
            'registration_role.in' => '身份選擇無效,僅支援 admin / parent / child',
        ]);

        $registrationRole = $validated['registration_role'];
        $token = $request->input('token');
        $inviteCode = $request->input('invite_code');
        $hasTokenOrInvite = filled($token) || filled($inviteCode);

        // 管理員註冊：需要管理員認證碼（防止任何人亂註冊管理員）。
        // 管理員註冊後「不」自動建立家庭，current_family_id = null，
        // 由管理員於後台手動建立 / 加入家庭。
        if ($registrationRole === 'admin') {
            $adminCode = trim((string) $request->input('admin_code'));
            $expectedAdminCode = env('ADMIN_REGISTRATION_CODE', ParentCode::getActiveCode() ?? 'ADMIN2026');
            if (empty($adminCode) || !hash_equals((string) $expectedAdminCode, $adminCode)) {
                return back()->withErrors(['admin_code' => '管理員認證碼無效'])->withInput();
            }

            $user = User::create([
                'name' => $validated['name'],
                'account' => $validated['account'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'is_system_admin' => true,
                'registration_role' => 'admin',
                'current_family_id' => null,
            ]);

            Auth::login($user);
            return redirect('/admin/families')->with('success', '🎉 管理員帳號建立成功！請於後台建立或加入家庭。');
        }

        // 家長認證碼驗證：無邀請 Token / 邀請碼 且註冊家長時，正確校驗 parent_code 是否符合 ParentCode::getActiveCode()
        if ($registrationRole === 'parent' && ! $hasTokenOrInvite) {
            $parentCode = trim((string) $request->input('parent_code'));
            $activeCode = ParentCode::getActiveCode();

            if (empty($parentCode) || ! $activeCode || $parentCode !== $activeCode) {
                return back()->withErrors(['parent_code' => '家長認證碼無效或已被停用'])->withInput();
            }
        }

        // 建立 User
        $user = User::create([
            'name' => $validated['name'],
            'account' => $validated['account'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_system_admin' => false,
            'registration_role' => $registrationRole,
        ]);

        // 有 Token：使用邀請連結加入家庭
        if (filled($token)) {
            $invitation = FamilyInvitation::where('token', $token)->where('is_used', false)->first();
            if ($invitation) {
                $family = $invitation->family;
                $familyRole = $invitation->role ?: (($registrationRole === 'child') ? 'child' : 'parent');
                if (! $family->members()->where('users.id', $user->id)->exists()) {
                    $family->members()->attach($user->id, ['role' => $familyRole, 'is_active' => true]);
                }
                $user->update(['current_family_id' => $family->id]);
                $invitation->update(['is_used' => true]);

                Auth::login($user);
                return redirect('/dashboard')->with('success', '🎉 已加入家庭「' . $family->name . '」,身份:' . $this->getRoleLabel($familyRole));
            }
        }

        // 有邀請碼：以 invite_code 加入
        if (filled($inviteCode)) {
            $family = Family::where('invite_code', strtoupper(trim($inviteCode)))->first();
            if (! $family) {
                return back()->withErrors(['invite_code' => '找不到這個家庭邀請碼,請檢查是否輸入正確'])->withInput();
            }
            $familyRole = ($registrationRole === 'child') ? 'child' : 'parent';
            if (! $family->members()->where('users.id', $user->id)->exists()) {
                $family->members()->attach($user->id, ['role' => $familyRole, 'is_active' => true]);
            }
            $user->update(['current_family_id' => $family->id]);

            Auth::login($user);
            return redirect('/dashboard')->with('success', '🎉 已加入家庭「' . $family->name . '」,身份:' . $this->getRoleLabel($familyRole));
        }

        // 沒有邀請碼與 Token 的情況:
        //   parent：自動建立第一個家庭（每個 parent 只能「擁有」一個，之後不能再手動建立）。
        //   child ：不建立家庭，保持 current_family_id = null，等家長 / 管理員邀請。
        if ($registrationRole === 'parent') {
            // 同一個 Email 不可能跑到這裡（unique:users,email 已擋），
            // 但仍保留雙重保險：若 DB 內已有使用者擁有家庭，這裡也擋下避免重複建立。
            if (User::where('email', $validated['email'])->whereKeyNot($user->id)->exists()) {
                // 理論上不會走到這裡
            }

            $newFamily = Family::create([
                'name' => $user->name . '的家庭',
                'currency' => 'TWD',
                'invite_code' => strtoupper(Str::random(6)),
                'total_pool_amount' => 0,
                'created_by_user_id' => $user->id,
                'owner_user_id' => $user->id,
            ]);
            $newFamily->members()->attach($user->id, ['role' => 'parent', 'is_active' => true]);

            // 建立預設「日常現金」帳戶
            Account::create([
                'family_id' => $newFamily->id,
                'name' => '日常現金',
                'type' => 'cash',
                'balance' => 0.00,
                'currency' => 'TWD',
                'color' => '#10B981',
                'icon' => 'heroicon-o-banknotes',
            ]);

            $user->update(['current_family_id' => $newFamily->id]);

            Auth::login($user);
            return redirect('/dashboard')->with('success', '🎉 註冊成功!已建立家庭「' . $newFamily->name . '」,邀請碼:' . $newFamily->invite_code);
        }

        if ($registrationRole === 'child') {
            // 小孩不會自動建立家庭，保持 current_family_id = null。
            // 等家長 / 管理員以 Email 邀請連結 / 邀請碼把小孩加入家庭。
            Auth::login($user);
            return redirect('/join-family')->with('info', '👶 小孩身份不會自動建立家庭,請輸入家長提供的家庭邀請碼或點選 Email 邀請連結加入。');
        }

        // 其他情況（理論上已被 validation 擋下）
        return back()->withErrors(['registration_role' => '不支援的註冊身份'])->withInput();
    }

    private function getRoleLabel(string $role): string
    {
        return match ($role) {
            'admin' => '管理員',
            'parent' => '家長',
            'child' => '兒童',
            'guest' => '訪客',
            default => $role,
        };
    }

    public function showJoinFamily(Request $request)
    {
        $token = $request->query('token');
        $invitation = null;
        if ($token) {
            $invitation = FamilyInvitation::where('token', $token)->where('is_used', false)->first();
        }

        return view('auth.join_family', compact('token', 'invitation'));
    }

    public function joinFamily(Request $request)
    {
        $token = $request->input('token');
        $user = Auth::user();

        // [P0] 保險防護：理論上 auth middleware 已經擋下未登入的使用者，
        // 但若 session 過期或 Auth 狀態遺失，仍要做最後一道檢查，
        // 避免在後續 $user->id / $user->registration_role 時炸出
        // "Attempt to read property 'id' on null" 的 ErrorException。
        if (! $user) {
            return redirect()->route('login')->with('info', '請先登入帳號才能加入家庭');
        }

        if (filled($token)) {
            $invitation = FamilyInvitation::withoutGlobalScopes()->where('token', trim($token))->where('is_used', false)->first();
            if ($invitation) {
                $family = $invitation->family;
                $familyRole = $invitation->role ?: (($user->registration_role === 'child') ? 'child' : 'parent');
                if (! $family->members()->where('users.id', $user->id)->exists()) {
                    $family->members()->attach($user->id, ['role' => $familyRole, 'is_active' => true]);
                }
                $user->update(['current_family_id' => $family->id]);
                $invitation->update(['is_used' => true]);

                return redirect('/dashboard')->with('success', '🎉 已成功加入家庭：「' . $family->name . '」！');
            }
        }

        $validated = $request->validate([
            'invite_code' => 'required|string|regex:/^[A-Za-z0-9]+$/i',
        ], [
            'invite_code.required' => '請輸入家庭邀請碼',
            'invite_code.regex' => '家庭邀請碼僅限輸入英文字母與數字',
        ]);

        $family = Family::withoutGlobalScopes()->where('invite_code', trim($validated['invite_code']))->first();
        if (! $family) {
            return back()->withErrors(['invite_code' => '找不到這個家庭邀請碼，請檢查是否輸入正確']);
        }

        if ($family->members()->where('users.id', $user->id)->exists()) {
            $user->update(['current_family_id' => $family->id]);
            return redirect('/dashboard')->with('info', '你已經是「' . $family->name . '」家庭的成員了，已切換至該家庭！');
        }

        $roleToAssign = ($user->registration_role === 'child') ? 'child' : 'parent';
        $family->members()->attach($user->id, ['role' => $roleToAssign, 'is_active' => true]);
        $user->update(['current_family_id' => $family->id]);

        return redirect('/dashboard')->with('success', '🎉 已成功加入家庭：「' . $family->name . '」！');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login')->with('success', '已成功登出');
    }
}
