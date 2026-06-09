<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GlEntity extends Model
{
    protected $table = 'gl_entities';

    protected $fillable = [
        'code',
        'name',
        'region',
        'ledger_code',
        'branch_id',
        'ledger_id_strategy',
        'ledger_id_list',
        'doc_header_template',
        'output_filename_template',
        'extraction_strategy',
        'company_code',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'ledger_id_list' => 'array',
        'is_active' => 'boolean',
    ];

    public function mappingProfiles(): HasMany
    {
        return $this->hasMany(GlMappingProfile::class, 'entity_id');
    }

    public function defaultProfile(): HasOne
    {
        return $this->hasOne(GlMappingProfile::class, 'entity_id')->where('is_default', true);
    }

    public function runHistories(): HasMany
    {
        return $this->hasMany(GlRunHistory::class, 'entity_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSemarang($query)
    {
        return $query->where('region', 'semarang');
    }

    public function scopeSurabaya($query)
    {
        return $query->where('region', 'surabaya');
    }

    public function getPythonServiceUrl(): string
    {
        return $this->region === 'semarang'
            ? config('services.python.semarang_url')
            : config('services.python.surabaya_url');
    }
}