<?php

namespace App\Services;

use App\Mail\TransactionAlertMail;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuditService
{
    public static function log(string $action, string $auditableType, ?int $auditableId, array $changes = [], ?string $title = null, ?string $content = null)
    {
        $user = auth()->user();
        $family = $user?->currentFamily;

        // 1. 寫入 AuditLog 資料庫
        $log = AuditLog::create([
            'family_id' => $family?->id,
            'user_id' => $user?->id,
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // 2. 如果提供 Email 通知且有登入者/家長 Email，經由 Google Mail API / SMTP 發送郵件
        if ($title && $content) {
            $recipientEmail = config('mail.from.address') ?: $user?->email;
            if ($recipientEmail) {
                try {
                    Mail::to($recipientEmail)->send(new TransactionAlertMail($title, $content, $changes));
                } catch (\Throwable $e) {
                    Log::warning("Google Mail API / SMTP 發送失敗: {$e->getMessage()}");
                }
            }

            // 3. 如果有設定 Discord Webhook URL，推播至 Discord
            $webhookUrl = env('DISCORD_WEBHOOK_URL');
            if ($webhookUrl) {
                try {
                    Http::post($webhookUrl, [
                        'username' => '家庭記帳小幫手',
                        'content' => "📢 **【{$title}】**\n{$content}",
                    ]);
                } catch (\Throwable $e) {
                    Log::warning("Discord Webhook 發送失敗: {$e->getMessage()}");
                }
            }
        }

        return $log;
    }
}
