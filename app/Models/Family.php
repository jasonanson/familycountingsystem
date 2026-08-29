<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Scopes\FamilyScope;

class Family extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'invite_code',
        'currency',
        'created_by_user_id',
        'owner_user_id',
        'total_pool_amount',
        'pool_currency',
        'settings',
        'discord_webhook_url',
        'storage_quota_mb',
        'is_archived',
        'archived_at',
    ];

    protected $casts = [
        'total_pool_amount' => 'decimal:2',
        'settings' => 'array',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new FamilyScope());
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'family_user')
            ->withPivot(['role', 'display_name', 'joined_at', 'is_active'])
            ->withTimestamps();
    }

    public function activeMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('is_active', true);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(FamilyInvitation::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
