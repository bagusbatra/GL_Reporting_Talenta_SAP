<?php

namespace Database\Seeders;

use App\Models\GlAccountPrefix;
use Illuminate\Database\Seeder;

class GlAccountPrefixSeeder extends Seeder
{
    public function run(): void
    {
        // Sumber: ACCOUNT_PREFIX di gl_text_filler.py
        $prefixes = [
            '2010000004' => 'Hutang BPJS',
            '2010000005' => 'Uang Titipan',
            '2010000006' => 'Uang Pengembalian Jaminan Tools',
            '2050000004' => 'PPh 21',
            '2050000005' => 'PPh 21',
            '5204000001' => 'Gaji',
            '5204000002' => 'Lembur',
            '5204000004' => 'Jamsostek',
            '5204000005' => 'Gaji',
            '5204000006' => 'Gaji',
            '5204000007' => 'Gaji',
            '5204000008' => 'Jamsostek',
            '5204000009' => 'Gaji',
            '5204000010' => 'Lembur',
            '5204000012' => 'Lembur',
            '7000000001' => 'Gaji',
            '7000000002' => 'Lembur',
            '7000000004' => 'Jamsostek',
            '7000000005' => 'Gaji',
            '7000000008' => 'Lembur',
        ];

        foreach ($prefixes as $account => $prefix) {
            GlAccountPrefix::updateOrCreate(
                ['account_number' => $account],
                ['prefix' => $prefix]
            );
        }

        $this->command->info('Seeded ' . count($prefixes) . ' account prefixes');
    }
}