<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Email;

/**
 * Gmail API (HTTPS, port 443) 寄信 transport
 *
 * 為什麼不直接用 SMTP？
 *   本機網路環境封鎖 465/587，但 443 通，
 *   所以改走 Gmail REST API（gmail.users.messages.send）。
 *
 * 流程：
 *   1. 用 refresh_token 換 access_token（POST oauth2.googleapis.com/token）
 *   2. 把 Laravel Mailable 轉成 RFC 2822 raw 訊息
 *   3. base64url 編碼後 POST 到 gmail.googleapis.com/gmail/v1/users/me/messages/send
 *
 * 設定 .env：
 *   GMAIL_API_CLIENT_ID=...
 *   GMAIL_API_CLIENT_SECRET=...
 *   GMAIL_API_CLIENT_MAIL=...
 *   GMAIL_API_CLIENT_REFRESH_TOKEN=...
 *
 * 使用：MAIL_MAILER=gmail-api
 */
class GmailApiTransport extends AbstractTransport
{
    private string $clientId;
    private string $clientSecret;
    private string $userEmail;
    private string $refreshToken;

    public function __construct(?\App\Services\GmailConnectionService $service = null)
    {
        parent::__construct();

        // 優先從 DB 讀，env 為 fallback（向下相容）
        $service = $service ?? app(\App\Services\GmailConnectionService::class);
        $creds = $service->getCredentials();

        $this->clientId     = (string) $creds['client_id'];
        $this->clientSecret = (string) $creds['client_secret'];
        $this->userEmail    = (string) $creds['user_email'];
        $this->refreshToken = (string) $creds['refresh_token'];
    }

    protected function doSend(SentMessage $message): void
    {
        if (! $this->refreshToken || ! $this->clientId || ! $this->userEmail) {
            throw new \RuntimeException('Gmail API 未設定（缺 refresh_token / client_id / email）— 請到後台 /admin/gmail-settings 完成連線，或在 .env 設定 GMAIL_API_* 變數');
        }

        $tokenResp = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (! $tokenResp->successful()) {
            throw new \RuntimeException('換 access_token 失敗：HTTP ' . $tokenResp->status() . ' / ' . $tokenResp->body());
        }
        $accessToken = $tokenResp->json('access_token');
        if (! $accessToken) {
            throw new \RuntimeException('access_token 缺失：' . $tokenResp->body());
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $raw = $this->buildRawMessage($email);

        $b64 = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
        $sendResp = Http::withToken($accessToken)
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                'raw' => $b64,
            ]);

        if (! $sendResp->successful()) {
            Log::error('Gmail API send 失敗', [
                'status' => $sendResp->status(),
                'body' => $sendResp->body(),
            ]);
            throw new \RuntimeException('Gmail API send 失敗：HTTP ' . $sendResp->status() . ' / ' . $sendResp->body());
        }

        Log::info('Gmail API send 成功', [
            'message_id' => $sendResp->json('id'),
            'thread_id' => $sendResp->json('threadId'),
            'to' => array_map(fn($a) => $a->getAddress(), $email->getTo()),
        ]);
    }

    private function buildRawMessage(Email $email): string
    {
        $headers = [];
        $headers[] = 'Message-ID: <' . bin2hex(random_bytes(8)) . '@homesync-finance>';
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'MIME-Version: 1.0';

        $from = $email->getFrom();
        if (! empty($from)) {
            $first = $from[0];
            $headers[] = 'From: ' . $this->formatAddress($first->getAddress(), $first->getName());
        }
        if (! empty($email->getTo())) {
            $headers[] = 'To: ' . implode(', ', array_map(
                fn($a) => $this->formatAddress($a->getAddress(), $a->getName()),
                $email->getTo()
            ));
        }
        if (! empty($email->getCc())) {
            $headers[] = 'Cc: ' . implode(', ', array_map(
                fn($a) => $this->formatAddress($a->getAddress(), $a->getName()),
                $email->getCc()
            ));
        }
        if (! empty($email->getBcc())) {
            $headers[] = 'Bcc: ' . implode(', ', array_map(
                fn($a) => $this->formatAddress($a->getAddress(), $a->getName()),
                $email->getBcc()
            ));
        }
        if ($replyTo = $email->getReplyTo()) {
            $headers[] = 'Reply-To: ' . implode(', ', array_map(
                fn($a) => $this->formatAddress($a->getAddress(), $a->getName()),
                $replyTo
            ));
        }
        if ($subject = $email->getSubject()) {
            $headers[] = 'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=';
        }

        $htmlBody = $email->getHtmlBody();
        $textBody = $email->getTextBody();

        if ($htmlBody !== null) {
            $boundary = '=_boundary_' . bin2hex(random_bytes(8));
            $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

            $body = '';
            if ($textBody !== null) {
                $body .= "--{$boundary}\r\n";
                $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $body .= chunk_split(base64_encode($textBody)) . "\r\n";
            }
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
            $body .= "--{$boundary}--\r\n";
        } else {
            $body = $textBody ?? '';
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            $headers[] = 'Content-Transfer-Encoding: base64';
            $body = chunk_split(base64_encode($body));
        }

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    private function formatAddress(string $address, string $name = ''): string
    {
        if (! $name) {
            return $address;
        }
        return '=?UTF-8?B?' . base64_encode($name) . "?= <{$address}>";
    }

    public function __toString(): string
    {
        return 'gmail-api';
    }
}