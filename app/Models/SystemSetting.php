<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    /**
     * 取得設定值（帶快取與預設值）
     */
    public static function get(string $key, $default = null)
    {
        try {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * 設定設定值
     */
    public static function set(string $key, $value, ?string $description = null)
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description ?? $key,
            ]
        );
    }
}
