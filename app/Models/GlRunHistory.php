<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlRunHistory extends Model
{
    protected $table = 'gl_run_histories';

    protected $fillable = [
        'entity_id',
        'profile_id',
        'period_year',
        'period_month',
        'status',
        'total_records',
        'total_debit',
        'total_credit',
        'output_file_path',
        'output_filled_path',
        'validation_status',
        'validation_details',
        'error_message',
        'run_by',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'validation_details' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_records' => 'integer',
        'total_debit' => 'integer',
        'total_credit' => 'integer',
        'period_year' => 'integer',
        'period_month' => 'integer',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(GlEntity::class, 'entity_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(GlMappingProfile::class, 'profile_id');
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('period_year', $year)->where('period_month', $month);
    }

    public function getPeriodLabelAttribute(): string
    {
        $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                   'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return ($months[$this->period_month] ?? '') . ' ' . $this->period_year;
    }

    public function getDifferenceAttribute(): int
    {
        return ($this->total_debit ?? 0) - ($this->total_credit ?? 0);
    }
}