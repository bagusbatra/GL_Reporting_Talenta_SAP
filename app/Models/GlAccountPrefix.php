<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlAccountPrefix extends Model
{
    protected $table = 'gl_account_prefixes';

    protected $fillable = [
        'account_number',
        'prefix',
    ];

    public static function getPrefix(string $accountNumber): ?string
    {
        $row = static::where('account_number', $accountNumber)->first();
        return $row?->prefix;
    }

    public static function allAsMap(): array
    {
        return static::pluck('prefix', 'account_number')->toArray();
    }
}