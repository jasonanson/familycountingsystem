<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Provider\Google;

/**
 * Gmail OAuth2 連線管理服務
 *
 * 用途：
 *   - 把原本寫死 .env 的 Gmail 設定搬到資料庫（加密儲存）
 *   - 提供「連線 / 斷線 / 取得 / 測試寄信」一組 API
 *   - 向下相容：DB 沒值時自動 fallback 到 .env
 *
 * 儲存位置：system_settings 表
 *   key: gmail.client_id          value: 明文（公開資訊）
 *   key: gmail.client_secret      value: Crypt 加密
 *   key: gmail.refresh_token      value: Crypt 加密
 *   key: gmail.user_email         value: 明文（寄信時需要）
 *   key: gmail.connected_at       value: ISO 8601 timestamp
 *   key: gmail.oauth_redirect_uri value: 完整 callback URL
 *
 * 注意：
 *   - 加密用 Laravel Crypt（APP_KEY 為基礎）— APP_KEY 換了就要重連
 *   - 一個安裝只支援一個 Gmail 帳號（要換帳號就斷線重連）
 */
class GmailConnectionService
{
    /** @var array<string, string> */
    private const KEY_MAP = [
        'client_id'     => 'gmail.client_id',
        'client_secret' => 'gmail.client_secret',
        'refresh_token' => 'gmail.refresh_token',
        'user_email'    => 'gmail.user_email',
        'connected_at'  => 'gmail.connected_at',
        'redirect_uri'  => 'gmail.oauth_redirect_uri',
    ];

    /**
     * 取得當前連線狀態（給後台 UI 顯示用，不含敏感資料）
     */
    public function getStatus(): array
    {
        $email = $this->get('user_email');
        $connectedAt = $this->get('connected_at');
        $clientId = $this->get('client_id');

        // fallback: 如果 DB 沒設 client_id，從 env 拿
        if (! $clientId) {
            $clientId = (string) env('GMAIL_API_CLIENT_ID', '');
        }

        return [
            'is_connected'       => (bool) ($email && $this->get('refresh_token')),
            'email'              => $email,
            'connected_at'       => $connectedAt,
            'client_id_preview'  => $clientId ? substr($clientId, 0, 20) . '…' : null,
            'client_id_full'     => $clientId, // 完整顯示在後台（後台僅管理員可見）
            'has_refresh_token'  => (bool) $this->get('refresh_token'),
            'redirect_uri'       => $this->getRedirectUri(),
            'env_fallback_used'  => ! $this->get('client_id') && (bool) env('GMAIL_API_CLIENT_ID'),
        ];
    }

    /**
     * 檢查是否已設定（不管是 DB 或 env）
     */
    public function isConfigured(): bool
    {
        $clientId = $this->get('client_id') ?: env('GMAIL_API_CLIENT_ID');
        $clientSecret = $this->getDecrypted('client_secret') ?: env('GMAIL_API_CLIENT_SECRET');
        $refreshToken = $this->getDecrypted('refresh_token') ?: env('GMAIL_API_CLIENT_REFRESH_TOKEN');
        $email = $this->get('user_email') ?: env('GMAIL_API_CLIENT_MAIL');

        return (bool) ($clientId && $clientSecret && $refreshToken && $email);
    }

    /**
     * 取得實際寄信用 credentials（DB 優先，env fallback）
     */
    public function getCredentials(): array
    {
        return [
            'client_id'     => $this->get('client_id')     ?: env('GMAIL_API_CLIENT_ID', ''),
            'client_secret' => $this->getDecrypted('client_secret') ?: env('GMAIL_API_CLIENT_SECRET', ''),
            'refresh_token' => $this->getDecrypted('refresh_token') ?: env('GMAIL_API_CLIENT_REFRESH_TOKEN', ''),
            'user_email'    => $this->get('user_email')    ?: env('GMAIL_API_CLIENT_MAIL', ''),
            'redirect_uri'  => $this->getRedirectUri(),
        ];
    }

    /**
     * 儲存 OAuth Client 憑證（尚未連線，只有 client_id + secret）
     */
    public function saveClientCredentials(string $clientId, string $clientSecret, ?string $redirectUri = null): void
    {
        SystemSetting::set(self::KEY_MAP['client_id'], $clientId, 'Gmail OAuth Client ID');
        SystemSetting::set(self::KEY_MAP['client_secret'], Crypt::encryptString($clientSecret), 'Gmail OAuth Client Secret（加密）');
        if ($redirectUri) {
            SystemSetting::set(self::KEY_MAP['redirect_uri'], $redirectUri, 'Gmail OAuth Redirect URI');
        }
    }

    /**
     * 處理 OAuth callback 回傳的 authorization code
     * 交換 refresh_token + email 並存入 DB
     */
    public function handleAuthorizationCode(string $code): array
    {
        $creds = $this->getCredentials();
        if (! $creds['client_id'] || ! $creds['client_secret']) {
            throw new \RuntimeException('尚未設定 OAuth Client ID / Secret');
        }

        $provider = new Google([
            'clientId'     => $creds['client_id'],
            'clientSecret' => $creds['client_secret'],
            'redirectUri'  => $creds['redirect_uri'],
        ]);

        $token = $provider->getAccessToken('authorization_code', ['code' => $code]);

        $refreshToken = $token->getRefreshToken();
        $accessToken = $token->getToken();
        $values = $token->getValues();

        // 取得使用者 email
        $email = $values['email'] ?? null;
        if (! $email) {
            $ownerDetails = $provider->getResourceOwner($token);
            $email = $ownerDetails ? $ownerDetails->getEmail() : null;
        }

        if (! $refreshToken) {
            throw new \RuntimeException('授權成功但未拿到 refresh_token（請到 Google 帳號「第三方應用程式」撤銷授權後重試）');
        }
        if (! $email) {
            throw new \RuntimeException('授權成功但無法取得授權的 email');
        }

        // 寫入 DB（加密敏感欄位）
        SystemSetting::set(self::KEY_MAP['refresh_token'], Crypt::encryptString($refreshToken), 'Gmail OAuth refresh_token（加密）');
        SystemSetting::set(self::KEY_MAP['user_email'], $email, 'Gmail 寄件者 email');
        SystemSetting::set(self::KEY_MAP['connected_at'], now()->toIso8601String(), 'Gmail 連線時間');

        // 也存一份 access_token 快取（雖然會過期，但能省一次 token exchange）
        // 不對：access_token 1 小時過期，存 DB 沒意義，省略。

        Log::info('Gmail OAuth2 連線成功', [
            'email' => $email,
            'has_refresh_token' => true,
            'source' => $this->get('client_id') ? 'database' : 'env_fallback',
        ]);

        return [
            'email'         => $email,
            'refresh_token' => $refreshToken, // 回傳明文給 callback 頁顯示一次（之後就只剩加密版）
            'access_token'  => $accessToken,
        ];
    }

    /**
     * 產生 OAuth 授權 URL（給「連接 Gmail」按鈕用）
     */
    public function buildAuthorizationUrl(): string
    {
        $creds = $this->getCredentials();
        if (! $creds['client_id'] || ! $creds['client_secret']) {
            throw new \RuntimeException('請先填寫 OAuth Client ID / Secret');
        }

        $provider = new Google([
            'clientId'     => $creds['client_id'],
            'clientSecret' => $creds['client_secret'],
            'redirectUri'  => $creds['redirect_uri'],
        ]);

        return $provider->getAuthorizationUrl([
            'scope' => [
                'https://www.googleapis.com/auth/gmail.send',
                'https://www.googleapis.com/auth/userinfo.email',
            ],
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);
    }

    /**
     * 斷線：清除所有 Gmail 相關設定（client_id/secret/refresh_token/email）
     */
    public function disconnect(): void
    {
        foreach (self::KEY_MAP as $key) {
            SystemSetting::where('key', $key)->delete();
        }
        Log::info('Gmail OAuth2 連線已斷開');
    }

    /**
     * 寄一封測試信（驗證連線是否正常）
     */
    public function sendTestEmail(string $to): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Gmail 尚未設定完成');
        }

        $creds = $this->getCredentials();

        // 換 access_token
        $tokenResp = \Illuminate\Support\Facades\Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id'     => $creds['client_id'],
            'client_secret' => $creds['client_secret'],
            'refresh_token' => $creds['refresh_token'],
            'grant_type'    => 'refresh_token',
        ]);

        if (! $tokenResp->successful()) {
            throw new \RuntimeException('換 access_token 失敗：HTTP ' . $tokenResp->status() . ' / ' . $tokenResp->body());
        }

        $accessToken = $tokenResp->json('access_token');

        // 組 raw email
        $subject = 'HomeSync Finance — Gmail 連線測試信';
        $body = "這是一封測試信，表示你的 Gmail OAuth 連線正常。\n\n寄件者：{$creds['user_email']}\n時間：" . now()->toDateTimeString();
        $raw = $this->buildRawEmail($creds['user_email'], $to, $subject, $body);
        $b64 = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

        $sendResp = \Illuminate\Support\Facades\Http::withToken($accessToken)
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', ['raw' => $b64]);

        if (! $sendResp->successful()) {
            throw new \RuntimeException('寄信失敗：HTTP ' . $sendResp->status() . ' / ' . $sendResp->body());
        }

        return [
            'success'    => true,
            'message_id' => $sendResp->json('id'),
            'to'         => $to,
            'from'       => $creds['user_email'],
        ];
    }

    /**
     * 取得 OAuth Redirect URI（DB 優先，env fallback，預設本機）
     */
    public function getRedirectUri(): string
    {
        return $this->get('redirect_uri')
            ?: env('GMAIL_OAUTH_REDIRECT_URI')
            ?: url('/oauth/gmail/callback');
    }

    /**
     * 從 DB 讀取值（明文）
     */
    private function get(string $logicalKey): ?string
    {
        if (! isset(self::KEY_MAP[$logicalKey])) {
            return null;
        }
        $value = SystemSetting::get(self::KEY_MAP[$logicalKey]);
        return $value !== null && $value !== '' ? (string) $value : null;
    }

    /**
     * 從 DB 讀取值（解密）
     */
    private function getDecrypted(string $logicalKey): ?string
    {
        $value = $this->get($logicalKey);
        if (! $value) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            Log::warning("Gmail 設定解密失敗 ({$logicalKey}): " . $e->getMessage());
            return null;
        }
    }

    private function buildRawEmail(string $from, string $to, string $subject, string $body): string
    {
        $headers = [];
        $headers[] = 'Message-ID: <' . bin2hex(random_bytes(8)) . '@homesync-finance>';
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'From: ' . $from;
        $headers[] = 'To: ' . $to;
        $headers[] = 'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: base64';

        return implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body));
    }
}
