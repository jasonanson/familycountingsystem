<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'is_active',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * 取得最新啟用的家長註冊碼字串
     */
    public static function getActiveCode(): ?string
    {
        $active = static::where('is_active', true)->latest()->first();
        return $active ? $active->code : env('PARENT_REGISTRATION_CODE', 'PARENT2026');
    }
}
