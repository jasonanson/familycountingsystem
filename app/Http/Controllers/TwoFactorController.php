<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TwoFactor\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * 兩步驟驗證 (2FA) 控制器（P2）。
 *
 * 流程：
 *   1. GET  /settings/2fa               — 顯示啟用 / 已啟用狀態頁
 *   2. POST /settings/2fa/enable        — 產生 secret + QR URL + 10 組恢復碼，存到 session 待確認
 *   3. POST /settings/2fa/confirm      — 使用者輸入 6 位 OTP 確認啟用，否則放棄
 *   4. POST /settings/2fa/disable      — 關閉 2FA（需先通過密碼驗證）
 *   5. POST /settings/2fa/regenerate-recovery — 重新產生 10 組恢復碼
 *   6. POST /2fa/verify                — 登入流程中的 2FA 驗證端點（OTP 或恢復碼）
 */
class TwoFactorController extends Controller
{
    public function __construct(protected TotpService $totp)
    {
    }

    /**
     * 2FA 設定頁
     */
    public function show()
    {
        $user = Auth::user();
        if (! $user) return redirect()->route('login');

        $pending = session('2fa.pending_secret');
        $recoveryCodes = session('2fa.pending_recovery_codes');

        return view('settings.two_factor', [
            'title' => '兩步驟驗證 (2FA)',
            'user' => $user,
            'pendingSecret' => $pending,
            'pendingRecoveryCodes' => $recoveryCodes,
        ]);
    }


    /**
     * 顯示登入後的 2FA 驗證表單。
     */
    public function showVerifyForm()
    {
        if (! session('2fa.user_id')) {
            return redirect()->route('login');
        }
        return view('auth.2fa.verify');
    }

    /**
     * 開始啟用流程：先產生 secret 暫存在 session，並回傳 QR 連結。
     * 不寫入 DB，使用者必須輸入正確 OTP 才會正式啟用。
     */
    public function enable(Request $request)
    {
        $user = Auth::user();
        if (! $user) return redirect()->route('login');
        if ($user->two_factor_enabled) {
            return back()->with('error', '2FA 已啟用，無需重複啟用。');
        }

        $secret = $this->totp->generateSecret();
        $recoveryCodes = $this->totp->generateRecoveryCodes();

        // 暫存到 session（加密避免被其他 session hijack 後讀取）
        session([
            '2fa.pending_secret' => encrypt($secret),
            '2fa.pending_recovery_codes' => $recoveryCodes, // 明文僅顯示一次
        ]);

        return redirect()->route('settings.2fa.show')->with('info', '請使用 Authenticator App 掃描 QR Code，並輸入 6 位數 OTP 完成啟用。');
    }

    /**
     * 確認啟用：使用者輸入 6 位 OTP，通過後 commit 到 DB。
     */
    public function confirm(Request $request)
    {
        $user = Auth::user();
        if (! $user) return redirect()->route('login');

        $pendingSecret = session('2fa.pending_secret');
        $pendingRecovery = session('2fa.pending_recovery_codes');
        if (! $pendingSecret || ! $pendingRecovery) {
            return back()->with('error', '待啟用資料已過期，請重新點選「啟用 2FA」。');
        }

        $validated = $request->validate([
            'code' => 'required|string|min:6|max:6|regex:/^\d{6}$/',
        ]);

        $secret = decrypt($pendingSecret);
        if (! $this->totp->verifyCode($secret, $validated['code'])) {
            return back()->withErrors(['code' => 'OTP 驗證失敗，請確認手機時間是否同步。'])->withInput();
        }

        // commit 到 DB
        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $this->totp->hashRecoveryCodes($pendingRecovery),
        ])->save();

        session()->forget(['2fa.pending_secret', '2fa.pending_recovery_codes']);

        return redirect()->route('settings.2fa.show')->with('success', '🎉 2FA 已成功啟用！請妥善保管下方恢復碼。');
    }

    /**
     * 關閉 2FA：要求使用者輸入密碼二次驗證。
     */
    public function disable(Request $request)
    {
        $user = Auth::user();
        if (! $user) return redirect()->route('login');
        if (! $user->two_factor_enabled) {
            return back()->with('error', '2FA 尚未啟用。');
        }

        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        if (! \Illuminate\Support\Facades\Hash::check($validated['password'], $user->password)) {
            return back()->withErrors(['password' => '密碼不正確，無法關閉 2FA。'])->withInput();
        }

        $this->totp->disableFor($user);

        return redirect()->route('settings.2fa.show')->with('success', '🔓 2FA 已關閉。');
    }

    /**
     * 重新產生 10 組恢復碼。
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $user = Auth::user();
        if (! $user || ! $user->two_factor_enabled) {
            return back()->with('error', '尚未啟用 2FA，無恢復碼可重新產生。');
        }

        $validated = $request->validate([
            'password' => 'required|string',
        ]);
        if (! \Illuminate\Support\Facades\Hash::check($validated['password'], $user->password)) {
            return back()->withErrors(['password' => '密碼不正確。'])->withInput();
        }

        $newCodes = $this->totp->regenerateRecoveryCodes($user);

        return redirect()->route('settings.2fa.show')->with([
            'success' => '🔄 已重新產生 10 組新恢復碼，舊碼全部失效。',
            'new_recovery_codes' => $newCodes,
        ]);
    }

    /**
     * 登入後的 2FA 驗證端點。
     * 使用者輸入 6 位 OTP 或恢復碼 → 通過後允許登入。
     */
    public function verify(Request $request)
    {
        $pendingUserId = session('2fa.user_id');
        if (! $pendingUserId) {
            return redirect()->route('login')->with('error', '驗證 session 已過期，請重新登入。');
        }

        $validated = $request->validate([
            'code' => 'required|string|min:5|max:11',
        ]);

        $user = User::find($pendingUserId);
        if (! $user || ! $user->two_factor_enabled || ! $user->two_factor_secret) {
            session()->forget('2fa.user_id');
            return redirect()->route('login')->with('error', '2FA 設定已變更，請重新登入。');
        }

        $secret = decrypt($user->two_factor_secret);
        $pass = false;

        // 先試 OTP
        if (preg_match('/^\d{6}$/', $validated['code'])) {
            $pass = $this->totp->verifyCode($secret, $validated['code']);
        }

        // 退路：嘗試恢復碼
        if (! $pass && is_array($user->two_factor_recovery_codes)) {
            $consume = $this->totp->consumeRecoveryCode($user->two_factor_recovery_codes, $validated['code']);
            if ($consume['ok']) {
                $user->two_factor_recovery_codes = $consume['remaining'];
                $user->save();
                $pass = true;
            }
        }

        if (! $pass) {
            return back()->withErrors(['code' => 'OTP / 恢復碼不正確，請重試。'])->withInput();
        }

        // 通過：完成登入
        Auth::login($user, (bool) session('2fa.remember'));
        session()->forget(['2fa.user_id', '2fa.remember']);

        // 2FA 通過後補上 family 保險絲，避免 family_id null Bug
        $user->ensureHasFamily();

        return redirect()->intended('/dashboard')->with('success', '歡迎回到 HomeSync Finance 家庭記帳！');
    }
}
