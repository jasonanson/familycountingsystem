<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'parent_id',
        'name',
        'icon',
        'color',
        'sort_order',
        'is_custom',
        'scope',
        'type',
        'is_archived',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_custom' => 'boolean',
        'is_archived' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\FamilyScope());

        static::saved(function ($model) {
            \Illuminate\Support\Facades\Cache::forget("categories_tree_family_{$model->family_id}");
            \Illuminate\Support\Facades\Cache::forget("categories_tree_family_0");
            \Illuminate\Support\Facades\Cache::forget("categories_tree_family_null");
            \Illuminate\Support\Facades\Cache::forget("categories_all_family_{$model->family_id}");
            \Illuminate\Support\Facades\Cache::forget("categories_all_family_0");
            \Illuminate\Support\Facades\Cache::forget("categories_expense_family_{$model->family_id}");
            \Illuminate\Support\Facades\Cache::forget("categories_expense_family_0");
            \Illuminate\Support\Facades\Cache::forget("categories_income_family_{$model->family_id}");
            \Illuminate\Support\Facades\Cache::forget("categories_income_family_0");
        });

        static::deleted(function ($model) {
            \Illuminate\Support\Facades\Cache::forget("categories_tree_family_{$model->family_id}");
            \Illuminate\Support\Facades\Cache::forget("categories_tree_family_0");
            \Illuminate\Support\Facades\Cache::forget("categories_tree_family_null");
            \Illuminate\Support\Facades\Cache::forget("categories_all_family_{$model->family_id}");
            \Illuminate\Support\Facades\Cache::forget("categories_all_family_0");
            \Illuminate\Support\Facades\Cache::forget("categories_expense_family_{$model->family_id}");
            \Illuminate\Support\Facades\Cache::forget("categories_expense_family_0");
            \Illuminate\Support\Facades\Cache::forget("categories_income_family_{$model->family_id}");
            \Illuminate\Support\Facades\Cache::forget("categories_income_family_0");
        });
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
