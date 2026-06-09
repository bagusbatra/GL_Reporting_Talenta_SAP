<?php

namespace App\Services;

use App\Models\GlAccountPrefix;
use App\Models\GlCostCenter;
use App\Models\GlRunHistory;
use App\Models\GlTextReference;
use Database\Seeders\GlAccountPrefixSeeder;
use Database\Seeders\GlCostCenterSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ResetCenterService
{
    /**
     * Get current stats untuk display di dashboard.
     */
    public function getStats(): array
    {
        $outputsDir = storage_path('app/gl_outputs');
        $filledDir = storage_path('app/gl_filled');
        $referencesDir = storage_path('app/gl_references');

        return [
            'run_histories' => GlRunHistory::count(),
            'text_references' => GlTextReference::count(),
            'account_prefixes' => GlAccountPrefix::count(),
            'cost_centers' => GlCostCenter::count(),
            'outputs_files' => $this->countFiles($outputsDir),
            'filled_files' => $this->countFiles($filledDir),
            'references_files' => $this->countFiles($referencesDir),
        ];
    }

    /**
     * Reset gl_run_histories + file output Excel.
     */
    public function resetRunHistories(): array
    {
        $deletedRows = 0;
        $deletedFiles = 0;

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            $deletedRows = GlRunHistory::count();
            GlRunHistory::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');

            $deletedFiles += $this->deleteFiles(storage_path('app/gl_outputs'));

            return [
                'success' => true,
                'message' => "Berhasil reset {$deletedRows} run history dan {$deletedFiles} file Excel output.",
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reset gl_text_references + file filled.
     */
    public function resetTextReferences(): array
    {
        $deletedRows = 0;
        $deletedFiles = 0;

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            $deletedRows = GlTextReference::count();
            GlTextReference::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');

            $deletedFiles += $this->deleteFiles(storage_path('app/gl_filled'));
            $deletedFiles += $this->deleteFiles(storage_path('app/gl_references'));

            return [
                'success' => true,
                'message' => "Berhasil reset {$deletedRows} text reference dan {$deletedFiles} file Excel (filled + reference).",
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reset gl_account_prefixes + auto re-seed.
     * [FIX] Detect silent fail: kalau seeder gak insert apa-apa, return failure.
     */
    public function resetAccountPrefixes(): array
    {
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            $deletedRows = GlAccountPrefix::count();
            GlAccountPrefix::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');

            // Capture seeder output for debugging
            Artisan::call('db:seed', ['--class' => GlAccountPrefixSeeder::class, '--force' => true]);
            $seederOutput = Artisan::output();
            $seededCount = GlAccountPrefix::count();

            // [FIX] Silent fail detection
            if ($seededCount === 0) {
                return [
                    'success' => false,
                    'error' => "Re-seed Account Prefixes gagal: 0 rows inserted. "
                            . "Cek file GlAccountPrefixSeeder.php. "
                            . "Output seeder: " . trim($seederOutput),
                ];
            }

            return [
                'success' => true,
                'message' => "Berhasil reset {$deletedRows} account prefix, lalu re-seed jadi {$seededCount} default prefix.",
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reset gl_cost_centers + auto re-seed dari COSTCENTER_SAP.xlsx.
     * [FIX] Detect silent fail: kalau file COSTCENTER_SAP.xlsx tidak ditemukan, return failure.
     */
    public function resetCostCenters(): array
    {
        try {
            // [FIX] Pre-check: pastikan file COSTCENTER_SAP.xlsx ada SEBELUM truncate
            $costCenterFile = storage_path('app/gl_uploads/COSTCENTER_SAP.xlsx');
            if (!file_exists($costCenterFile)) {
                return [
                    'success' => false,
                    'error' => "File COSTCENTER_SAP.xlsx tidak ditemukan di storage/app/gl_uploads/. "
                            . "Upload file ini dulu sebelum reset, kalau tidak data Cost Centers akan kosong.",
                ];
            }

            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            $deletedRows = GlCostCenter::count();
            GlCostCenter::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');

            Artisan::call('db:seed', ['--class' => GlCostCenterSeeder::class, '--force' => true]);
            $seederOutput = Artisan::output();
            $seededCount = GlCostCenter::count();

            // [FIX] Silent fail detection
            if ($seededCount === 0) {
                return [
                    'success' => false,
                    'error' => "Re-seed Cost Centers gagal: 0 rows inserted. "
                            . "File COSTCENTER_SAP.xlsx mungkin corrupt atau format tidak sesuai. "
                            . "Output seeder: " . trim($seederOutput),
                ];
            }

            return [
                'success' => true,
                'message' => "Berhasil reset {$deletedRows} cost center, lalu re-seed jadi {$seededCount} cost center dari COSTCENTER_SAP.xlsx.",
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reset all - jalankan semua reset secara berurutan.
     */
    public function resetAll(): array
    {
        $results = [];
        $totalSuccess = 0;
        $totalFailed = 0;

        $sections = [
            'Run Histories' => 'resetRunHistories',
            'Text References' => 'resetTextReferences',
            'Account Prefixes' => 'resetAccountPrefixes',
            'Cost Centers' => 'resetCostCenters',
        ];

        foreach ($sections as $name => $method) {
            $result = $this->$method();
            $results[$name] = $result;
            if ($result['success']) {
                $totalSuccess++;
            } else {
                $totalFailed++;
            }
        }

        return [
            'success' => $totalFailed === 0,
            'total_success' => $totalSuccess,
            'total_failed' => $totalFailed,
            'details' => $results,
        ];
    }

    /**
     * Helper: hitung jumlah file di folder.
     */
    private function countFiles(string $dir): int
    {
        if (!is_dir($dir)) return 0;
        $files = glob($dir . '/*.xlsx');
        return $files ? count($files) : 0;
    }

    /**
     * Helper: hapus semua file .xlsx di folder.
     */
    private function deleteFiles(string $dir): int
    {
        if (!is_dir($dir)) return 0;

        $files = glob($dir . '/*.xlsx');
        if (empty($files)) return 0;

        $count = 0;
        foreach ($files as $file) {
            if (@unlink($file)) {
                $count++;
            }
        }
        return $count;
    }
}