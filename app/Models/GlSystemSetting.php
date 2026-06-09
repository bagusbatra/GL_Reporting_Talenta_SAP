<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlSystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    /**
     * Get value by key (with default fallback).
     */
    public static function getValue(string $key, ?string $default = null): ?string
    {
        $setting = self::where('key', $key)->first();
        return $setting?->value ?? $default;
    }

    /**
     * Set value by key (insert atau update).
     */
    public static function setValue(string $key, ?string $value, ?string $description = null): self
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
            ]
        );
    }
}