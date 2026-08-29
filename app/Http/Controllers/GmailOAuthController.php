<?php

namespace App\Http\Controllers;

use App\Services\GmailConnectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Provider\Google;

/**
 * Gmail OAuth2 一次性授權流程控制器。
 *
 * 流程：
 *   1. 家長點 /oauth/gmail -> 本控制器 redirect 到 Google OAuth
 *   2. Google 授權後 redirect 回 /oauth/gmail/callback?code=...
 *   3. 本控制器用 code 換 refresh_token
 *   4. 寫進 storage/app/gmail-oauth-token.json
 *   5. 提示使用者把 refresh_token 抄到 .env 的 GMAIL_API_CLIENT_REFRESH_TOKEN
 *
 * 詳細步驟見 docs/EMAIL_SETUP.md
 */
class GmailOAuthController extends Controller
{
    /**
     * 把使用者導向 Google OAuth 授權頁。
     */
    public function redirect(Request $request, GmailConnectionService $service)
    {
        try {
            $authUrl = $service->buildAuthorizationUrl();
        } catch (\Throwable $e) {
            return response()->view('errors.oauth_not_configured', [
                'missing' => ['error' => $e->getMessage()],
                'admin_url' => url('/admin/gmail-settings'),
            ], 500);
        }

        $provider = new Google($service->getCredentials());
        session(['gmail_oauth_state' => $provider->getState()]);

        return redirect()->away($authUrl);
    }

    /**
     * Google 授權後 redirect 回來的 callback。
     * 流程：把 code 交給 GmailConnectionService，service 寫入資料庫（加密）。
     */
    public function callback(Request $request, GmailConnectionService $service)
    {
        $code = $request->query('code');
        $state = $request->query('state');
        $error = $request->query('error');

        if ($error) {
            return response()->view('emails.oauth_result', [
                'success' => false,
                'message' => "Google 拒絕授權：{$error}",
                'refreshToken' => null,
            ], 400);
        }

        if (! $code) {
            return response()->view('emails.oauth_result', [
                'success' => false,
                'message' => '授權碼缺失，請重新從後台 Gmail 設定頁開始。',
                'refreshToken' => null,
            ], 400);
        }

        // CSRF check
        $expected = session('gmail_oauth_state');
        if ($expected && $state && $state !== $expected) {
            return response()->view('emails.oauth_result', [
                'success' => false,
                'message' => 'State 不一致（CSRF 風險），請重新從後台 Gmail 設定頁開始。',
                'refreshToken' => null,
            ], 400);
        }
        session()->forget('gmail_oauth_state');

        try {
            $result = $service->handleAuthorizationCode($code);

            // 也順手把 refresh_token + email 寫到 .env（向下相容舊行為）
            if (! empty($result['refresh_token']) && ! empty($result['email'])) {
                $this->writeToEnv($result['refresh_token'], $result['email']);
            }

            return response()->view('emails.oauth_result', [
                'success' => true,
                'message' => "已成功連線 Gmail！帳號：{$result['email']}。你可以回後台 Gmail 設定頁繼續測試寄信。",
                'refreshToken' => $result['refresh_token'],
                'email' => $result['email'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Gmail OAuth2 失敗', ['error' => $e->getMessage()]);
            return response()->view('emails.oauth_result', [
                'success' => false,
                'message' => '授權碼交換失敗：' . $e->getMessage(),
                'refreshToken' => null,
            ], 500);
        }
    }

    /**
     * 顯示目前 OAuth 狀態。
     */
    public function status(Request $request, GmailConnectionService $service)
    {
        $status = $service->getStatus();
        $configured = $service->isConfigured();
        $tokenPath = storage_path('app/gmail-oauth-token.json');
        $tokenExists = file_exists($tokenPath);
        $tokenPayload = $tokenExists ? json_decode(file_get_contents($tokenPath), true) : null;

        return response()->view('emails.oauth_status', [
            'configured' => $configured,
            'redirectUri' => $status['redirect_uri'],
            'clientId' => $status['client_id_full'],
            'clientEmail' => $status['email'],
            'tokenExists' => $tokenExists,
            'tokenPayload' => $tokenPayload,
            'authUrl' => url('/oauth/gmail'),
            'mailMailer' => env('MAIL_MAILER'),
        ]);
    }

    /**
     * 把 refresh_token + email 寫進 .env
     */
    protected function writeToEnv(string $refreshToken, string $email): void
    {
        $envPath = base_path('.env');
        if (! is_writable($envPath)) {
            Log::warning('.env 不可寫，請手動填入 GMAIL_API_CLIENT_REFRESH_TOKEN');
            return;
        }

        $env = file_get_contents($envPath);
        $env = preg_replace(
            '/^GMAIL_API_CLIENT_REFRESH_TOKEN=.*$/m',
            'GMAIL_API_CLIENT_REFRESH_TOKEN=' . $refreshToken,
            $env
        );
        $env = preg_replace(
            '/^GMAIL_API_CLIENT_MAIL=.*$/m',
            'GMAIL_API_CLIENT_MAIL=' . $email,
            $env
        );
        file_put_contents($envPath, $env);
    }
}