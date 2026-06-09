<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlCostCenter extends Model
{
    protected $table = 'gl_cost_centers';

    protected $fillable = [
        'cost_center_code',
        'name',
        'description',
        'short_text',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('cost_center_code', $code)->first();
    }

    public function getDisplayDescriptionAttribute(): string
    {
        return $this->description ?: $this->name ?: $this->cost_center_code;
    }
}