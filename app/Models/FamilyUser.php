<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyUser extends Model
{
    use HasFactory;

    protected $table = 'family_user';

    protected $fillable = [
        'family_id',
        'user_id',
        'role',
        'display_name',
        'joined_at',
        'is_active',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeParents($query)
    {
        return $query->where('role', 'parent');
    }

    public function scopeChildren($query)
    {
        return $query->where('role', 'child');
    }
}
