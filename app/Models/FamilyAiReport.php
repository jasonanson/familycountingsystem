<?php

namespace App\Models;

use App\Models\Scopes\FamilyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyAiReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'created_by_user_id',
        'month',
        'financial_metrics',
        'ai_report',
        'status',
        'sent_to_users_count',
    ];

    protected $casts = [
        'financial_metrics' => 'array',
        'sent_to_users_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new FamilyScope());
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
