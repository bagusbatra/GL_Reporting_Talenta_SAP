<?php

namespace Database\Seeders;

use App\Models\GlCostCenter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;

class GlCostCenterSeeder extends Seeder
{
    public function run(): void
    {
        $file = storage_path('app/gl_uploads/COSTCENTER_SAP.xlsx');

        if (!file_exists($file)) {
            $this->command->warn("COSTCENTER_SAP.xlsx tidak ditemukan di storage/app/gl_uploads/");
            $this->command->warn("Skip cost center seeding. Upload file lalu jalankan: php artisan db:seed --class=GlCostCenterSeeder");
            return;
        }

        $rows = Excel::toArray(new class implements ToArray {
            public function array(array $array)
            {
                return $array;
            }
        }, $file);

        if (empty($rows) || empty($rows[0])) {
            $this->command->warn('File COSTCENTER_SAP.xlsx kosong atau tidak bisa dibaca');
            return;
        }

        // Sheet pertama, ambil semua row
        $sheetRows = $rows[0];
        $header = array_map('trim', $sheetRows[0] ?? []);

        // Cari index kolom yang kita butuhin
        $idxCode = array_search('Cost Center', $header);
        $idxName = array_search('Name', $header);
        $idxDesc = array_search('Description', $header);
        $idxShort = array_search('Cost Ctr Short Text', $header);

        if ($idxCode === false) {
            $this->command->error('Kolom "Cost Center" tidak ditemukan di header file');
            return;
        }

        $inserted = 0;
        $skipped = 0;
        $batchData = [];

        for ($i = 1; $i < count($sheetRows); $i++) {
            $row = $sheetRows[$i];
            $code = trim($row[$idxCode] ?? '');

            // Skip jika code kosong atau bukan 10-digit code
            if (empty($code) || strlen($code) !== 10) {
                $skipped++;
                continue;
            }

            $batchData[$code] = [
                'cost_center_code' => $code,
                'name' => $idxName !== false ? trim($row[$idxName] ?? '') : null,
                'description' => $idxDesc !== false ? trim($row[$idxDesc] ?? '') : null,
                'short_text' => $idxShort !== false ? trim($row[$idxShort] ?? '') : null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Bulk insert dengan upsert (unique by cost_center_code)
        $chunks = array_chunk(array_values($batchData), 200);
        foreach ($chunks as $chunk) {
            GlCostCenter::upsert(
                $chunk,
                ['cost_center_code'],
                ['name', 'description', 'short_text', 'updated_at']
            );
            $inserted += count($chunk);
        }

        $this->command->info("Seeded {$inserted} cost centers (skipped {$skipped} non 10-digit codes)");
    }
}