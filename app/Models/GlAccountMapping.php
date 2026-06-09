<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlAccountMapping extends Model
{
    protected $table = 'gl_account_mappings';

    protected $fillable = [
        'profile_id',
        'mapping_key',
        'account_number',
        'account_name',
        'account_type',
        'transaction_value',
        'cost_center',
        'profit_center',
        'use_profit_center',
        'components',
        'match_account_name',
        'match_keywords',
        'order_index',
        'is_active',
    ];

    protected $casts = [
        'use_profit_center' => 'boolean',
        'is_active' => 'boolean',
        'components' => 'array',
        'match_keywords' => 'array',
        'order_index' => 'integer',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(GlMappingProfile::class, 'profile_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDebit($query)
    {
        return $query->where('transaction_value', 'Debit');
    }

    public function scopeCredit($query)
    {
        return $query->where('transaction_value', 'Credit');
    }
}