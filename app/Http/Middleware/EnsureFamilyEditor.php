<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureFamilyEditor — 確保當前使用者可以編輯當前家庭的資料。
 *
 * 規則：透過 family_user 樞紐，以 'parent' 角色存在於當前家庭才能編輯。
 * 系統管理員若未被家庭家長邀請加入此家庭（family_user 中沒有 parent 角色），
 * 會被擋下（403）。
 *
 * 設計目的：
 *   - 管理員可在後台總覽每個家庭的資料（FamilyScope 全域 bypass）
 *   - 管理員切換至特定家庭後，只能檢視，無法新增/修改/刪除家庭內資料
 *   - 若管理員想編輯，必須先由該家庭的 parent 透過邀請加入
 *
 * 白名單（不檢查編輯權限）：
 *   - GET / HEAD 請求（唯讀）
 *   - admin.* 路由（後台管理）
 *   - 登入/註冊/2FA/settings/profile 等使用者本身設定
 *   - family.switch（切換家庭）
 *   - invitation.accept（接受邀請連結）
 *   - locale.* / verify.* / 2fa 驗證
 */
class EnsureFamilyEditor
{
    /**
     * 路由白名單（支援 Laravel Str::is 比對的 pattern）
     */
    protected array $whitelist = [
        // 使用者身份/帳號相關
        'login', 'logout', 'register', 'password.*', 'verification.*', 'verify.*',
        '2fa.verify', 'settings.2fa.*',

        // 使用者個人資料
        'settings.profile.*', 'settings.notifications.*',

        // 多語言
        'locale.*',

        // 家庭切換（即使沒有 family_user 也要允許切換）
        'family.switch',

        // 加入家庭（透過邀請碼 / Token 進入別的家庭）
        'join-family',

        // 接受家庭邀請連結（讓 admin 可透過連結變成家庭的家長）
        'invitation.accept',

        // 後台管理（admin 自己的管理介面，不算家庭內資料）
        'admin.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        // GET / HEAD / OPTIONS 為唯讀操作，不檢查
        $method = strtoupper($request->method());
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        // 檢查路由白名單
        $route = $request->route();
        $name = $route?->getName();

        // 用路由名稱做比對（preferred）
        if ($name) {
            foreach ($this->whitelist as $pattern) {
                if (Str::is($pattern, $name)) {
                    return $next($request);
                }
            }
        } else {
            // 沒有 route name 時，用 path 比對（fallback）
            $path = trim($request->path(), '/');
            foreach ($this->fallbackWhitelist as $pattern) {
                if (Str::is($pattern, $path)) {
                    return $next($request);
                }
            }
        }

        $user = Auth::user();

        if (! $user->canEditCurrentFamily()) {
            $msg = '⚠️ 存取拒絕：系統管理員需先由家庭家長邀請加入此家庭後，才能編輯資料。';

            // JSON / AJAX 請求回傳 403 JSON
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 403);
            }

            // 一般請求 → 回到原頁面並顯示錯誤
            return back()->with('error', $msg);
        }

        return $next($request);
    }

    /**
     * 沒有 route name 時的路徑白名單（fallback）
     */
    protected array $fallbackWhitelist = [
        'login', 'logout', 'register',
        '2fa/verify',
        'locale/*',
        'family/switch',
        'join-family',
        'invitation/accept/*',
        'admin/*',
    ];
}
