<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 信任所有代理伺服器與網域穿透隧道 (Tailscale .ts.net, Ngrok, Cloudflare 等) 防護 419 CSRF Page Expired
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: [
            'notifications/*',
            'notifications',
        ]);

        // === 多語言中介層：對所有 web 群組路由自動套用 ===
        // 解析順序：URL ?lang= → users.locale → session → SystemSetting.default_locale → APP_LOCALE
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        // === 家庭編輯權限中介層 ===
        // 確保管理員若未透過家庭家長邀請加入，無法修改家庭內資料。
        // 中介層內部會自動跳過 GET、未登入、白名單路由（如 admin.* / login / 2FA 等），
        // 因此可以安全地全域套用。
        $middleware->web(append: [
            \App\Http\Middleware\EnsureFamilyEditor::class,
        ]);

        // 別名（保留供路由單獨使用）
        $middleware->alias([
            'family.editor' => \App\Http\Middleware\EnsureFamilyEditor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
