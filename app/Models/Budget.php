<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'period_type',
        'period_start',
        'period_end',
        'scope',
        'scope_target_ids',
        'amount',
        'alert_thresholds',
        'rollover',
        'parent_budget_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'scope_target_ids' => 'array',
        'alert_thresholds' => 'array',
        'rollover' => 'boolean',
    ];

    protected $appends = [
        'spent_amount',
        'remaining_amount',
        'usage_percentage',
        'status',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\FamilyScope());
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function parentBudget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'parent_budget_id');
    }

    public function subBudgets(): HasMany
    {
        return $this->hasMany(Budget::class, 'parent_budget_id');
    }

    /**
     * 計算該預算區間 (period_start ~ period_end) 且符合 scope 之支出交易總額
     */
    public function getSpentAmountAttribute(): float
    {
        $query = Transaction::where('type', 'expense');

        if ($this->period_start && $this->period_end) {
            $query->whereBetween('occurred_at', [
                $this->period_start->copy()->startOfDay(),
                $this->period_end->copy()->endOfDay(),
            ]);
        } elseif ($this->period_start) {
            $query->where('occurred_at', '>=', $this->period_start->copy()->startOfDay());
        } elseif ($this->period_end) {
            $query->where('occurred_at', '<=', $this->period_end->copy()->endOfDay());
        }

        if ($this->scope === 'category' && !empty($this->scope_target_ids)) {
            $catIds = is_array($this->scope_target_ids) ? $this->scope_target_ids : [$this->scope_target_ids];
            $childCatIds = Category::whereIn('parent_id', $catIds)->pluck('id')->toArray();
            $allCatIds = array_unique(array_merge($catIds, $childCatIds));
            $query->whereIn('category_id', $allCatIds);
        } elseif ($this->scope === 'user' && !empty($this->scope_target_ids)) {
            $userIds = is_array($this->scope_target_ids) ? $this->scope_target_ids : [$this->scope_target_ids];
            $query->whereIn('user_id', $userIds);
        }

        return (float) $query->sum('amount');
    }

    /**
     * 剩餘預算金額
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0.0, (float) $this->amount - $this->spent_amount);
    }

    /**
     * 預算使用百分比 (0 ~ N%)
     */
    public function getUsagePercentageAttribute(): float
    {
        if ((float) $this->amount > 0) {
            return round(($this->spent_amount / (float) $this->amount) * 100, 1);
        }

        return 0.0;
    }

    /**
     * 預算使用狀態 (normal, warning, danger)
     */
    public function getStatusAttribute(): string
    {
        $usage = $this->usage_percentage;
        if ($usage >= 100) {
            return 'danger';
        }
        if ($usage >= 80) {
            return 'warning';
        }
        return 'normal';
    }

    /**
     * 當 scope === 'category' 且 scope_target_ids 有值時，關聯或查詢對應的 Category 模型
     */
    public function getCategoryAttribute(): ?Category
    {
        if ($this->scope === 'category' && !empty($this->scope_target_ids)) {
            $firstId = is_array($this->scope_target_ids) ? ($this->scope_target_ids[0] ?? null) : $this->scope_target_ids;
            return $firstId ? Category::find($firstId) : null;
        }

        return null;
    }
}
