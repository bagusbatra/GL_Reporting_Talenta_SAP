<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlStrategyDConfig extends Model
{
    protected $table = 'gl_strategy_d_configs';

    protected $fillable = [
        'profile_id',
        'debit_accounts',
        'debit_keywords',
        'default_dc',
    ];

    protected $casts = [
        'debit_accounts' => 'array',
        'debit_keywords' => 'array',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(GlMappingProfile::class, 'profile_id');
    }
}