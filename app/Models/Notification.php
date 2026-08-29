<?php

namespace App\Models;

use App\Mail\NotificationAlertMail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Notification extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'family_id',
        'type',
        'title',
        'body',
        'channel',
        'related_entity',
        'read_at',
        'sent_at',
    ];

    protected $casts = [
        'related_entity' => 'array',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::created(function (Notification $notification) {
            try {
                // 檢查：通知類型是否有被開啟 email 頻道
                if (! self::shouldSendEmail($notification)) {
                    // 使用者關閉了 email、或通知類型不在偏好設定中
                    return;
                }

                $user = $notification->user;
                if ($user && !empty($user->email)) {
                    Mail::to($user->email)->send(new NotificationAlertMail($notification));
                    $notification->updateQuietly(['sent_at' => now()]);
                }
            } catch (\Throwable $e) {
                Log::error("Failed sending NotificationAlertMail for notification {$notification->id}: " . $e->getMessage());
            }
        });
    }

    /**
     * 判斷這個通知是否要寄 email
     *
     * 規則：
     *   1. 若使用者的 notification_preferences['channels']['email'] = false → 不寄
     *   2. 若 notification_preferences['preferences'][$type]['email'] = false → 不寄
     *   3. 若 $type 不在偏好設定中 → 預設寄（向後相容）
     */
    public static function shouldSendEmail(Notification $notification): bool
    {
        $user = $notification->user;
        if (! $user) {
            return false;
        }

        $prefs = $user->notification_preferences;
        if (! is_array($prefs)) {
            return true; // 沒設過偏好 → 預設寄
        }

        // 整體 email 頻道關閉
        if (isset($prefs['channels']['email']) && $prefs['channels']['email'] === false) {
            return false;
        }

        // 個別事件 email 設定
        $eventPrefs = $prefs['preferences'][$notification->type] ?? null;
        if ($eventPrefs === null) {
            return true; // 此事件沒在偏好清單 → 預設寄
        }
        if (isset($eventPrefs['email']) && $eventPrefs['email'] === false) {
            return false;
        }

        return true;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, $user)
    {
        $userId = $user instanceof User ? $user->id : $user;
        return $query->where('user_id', $userId);
    }
}
