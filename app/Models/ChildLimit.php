<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'child_user_id',
        'per_transaction_max',
        'daily_max',
        'weekly_max',
        'monthly_max',
        'ratio_of_pocket',
        'overrides',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'per_transaction_max' => 'decimal:2',
        'daily_max' => 'decimal:2',
        'weekly_max' => 'decimal:2',
        'monthly_max' => 'decimal:2',
        'ratio_of_pocket' => 'decimal:2',
        'overrides' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\FamilyScope());
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function childUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_user_id');
    }
}
