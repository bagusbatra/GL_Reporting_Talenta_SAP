<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class GlTextReference extends Model
{
    protected $table = 'gl_text_references';

    protected $fillable = [
        'account_number',
        'cost_center',
        'text_value',
        'learned_from',
        'use_count',
        'last_used_at',
    ];

    protected $casts = [
        'use_count' => 'integer',
        'last_used_at' => 'datetime',
    ];

    public static function findReference(string $accountNumber, ?string $costCenter = null): ?self
    {
        return static::where('account_number', $accountNumber)
            ->where('cost_center', $costCenter)
            ->first();
    }

    public static function learnOrUpdate(
        string $accountNumber,
        ?string $costCenter,
        string $textValue,
        ?string $source = null
    ): self {
        $row = static::firstOrNew([
            'account_number' => $accountNumber,
            'cost_center' => $costCenter,
        ]);

        $row->text_value = $textValue;
        $row->learned_from = $source ?: $row->learned_from ?: 'manual_input';
        $row->use_count = ($row->use_count ?? 0) + 1;
        $row->last_used_at = Carbon::now();
        $row->save();

        return $row;
    }

    public static function buildLookupMap(): array
    {
        $map = [];
        static::query()->chunk(500, function ($items) use (&$map) {
            foreach ($items as $item) {
                $key = $item->account_number . '|' . ($item->cost_center ?? '');
                $map[$key] = $item->text_value;
            }
        });
        return $map;
    }
}