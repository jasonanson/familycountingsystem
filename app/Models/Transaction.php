<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'family_id',
        'user_id',
        'type',
        'type_custom',
        'amount',
        'occurred_at',
        'account_id',
        'to_account_id',
        'category_id',
        'payee_user_id',
        'payee_custom',
        'description',
        'note',
        'attachment_ids',
        'tag_ids',
        'split_with',
        'recurring_rule_id',
        'refunded_from_id',
        'custom_fields',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'occurred_at' => 'datetime',
        'attachment_ids' => 'array',
        'tag_ids' => 'array',
        'split_with' => 'array',
        'custom_fields' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\FamilyScope());
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function payeeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payee_user_id');
    }

    public function recurringRule(): BelongsTo
    {
        return $this->belongsTo(RecurringRule::class);
    }

    public function refundedFrom(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'refunded_from_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Transaction::class, 'refunded_from_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}

