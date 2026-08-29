<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'type',
        'template',
        'cycle',
        'cycle_custom',
        'next_run_at',
        'last_run_at',
        'alert_days_before',
        'auto_create',
    ];

    protected $casts = [
        'template' => 'array',
        'cycle_custom' => 'array',
        'alert_days_before' => 'array',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'auto_create' => 'boolean',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
