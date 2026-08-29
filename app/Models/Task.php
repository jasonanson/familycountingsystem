<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'name',
        'reward_type',
        'reward_amount',
        'reward_custom',
        'assignee_user_id',
        'status',
        'due_date',
        'reported_at',
        'approved_at',
        'approved_by_user_id',
        'reject_reason',
    ];

    protected $casts = [
        'reward_amount' => 'decimal:2',
        'due_date' => 'date',
        'reported_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
