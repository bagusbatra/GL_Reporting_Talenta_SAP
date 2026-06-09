<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GlMappingProfile extends Model
{
    protected $table = 'gl_mapping_profiles';

    protected $fillable = [
        'entity_id',
        'name',
        'is_default',
        'description',
        'created_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(GlEntity::class, 'entity_id');
    }

    public function accountMappings(): HasMany
    {
        return $this->hasMany(GlAccountMapping::class, 'profile_id')->orderBy('order_index');
    }

    public function strategyDConfig(): HasOne
    {
        return $this->hasOne(GlStrategyDConfig::class, 'profile_id');
    }

    public function runHistories(): HasMany
    {
        return $this->hasMany(GlRunHistory::class, 'profile_id');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}