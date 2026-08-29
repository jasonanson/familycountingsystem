<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'name',
        'amount',
        'cycle',
        'cycle_custom',
        'next_billing_date',
        'account_id',
        'category_id',
        'shared_with',
        'trial_until',
        'url',
        'note',
        'is_paused',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cycle_custom' => 'array',
        'next_billing_date' => 'date',
        'shared_with' => 'array',
        'trial_until' => 'date',
        'is_paused' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\FamilyScope());
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
