<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'inviter_user_id',
        'email',
        'token',
        'role',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class)->withoutGlobalScopes();
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_user_id');
    }

    /**
     * 檢查邀請碼是否有效（未被使用且未過期）
     */
    public function isValid(): bool
    {
        if ($this->used_at !== null) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
