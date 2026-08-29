<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'name',
        'type',
        'type_custom',
        'balance',
        'currency',
        'color',
        'icon',
        'is_archived',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_archived' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\FamilyScope());

        static::saved(function ($model) {
            \Illuminate\Support\Facades\Cache::forget("accounts_family_{$model->family_id}");
            \Illuminate\Support\Facades\Cache::forget("accounts_family_0");
        });

        static::deleted(function ($model) {
            \Illuminate\Support\Facades\Cache::forget("accounts_family_{$model->family_id}");
            \Illuminate\Support\Facades\Cache::forget("accounts_family_0");
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'account_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(Transaction::class, 'to_account_id');
    }
}
