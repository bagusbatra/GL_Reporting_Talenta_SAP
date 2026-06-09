<?php

namespace Database\Seeders;

use App\Models\GlAccountMapping;
use App\Models\GlEntity;
use App\Models\GlMappingProfile;
use App\Models\GlStrategyDConfig;
use Illuminate\Database\Seeder;

class GlSurabayaMappingSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDriverKmi();
        $this->seedKmi2();
        $this->seedKmi1();
        $this->seedPembantuKmi();
        $this->seedKaryawanHarianLepas();
        $this->seedNonStaffKmi();
        $this->seedStaffKmi();

        $this->command->info('Seeded default mappings for 7 Surabaya entities');
    }

    private function createProfile(string $entityCode): GlMappingProfile
    {
        $entity = GlEntity::where('code', $entityCode)->firstOrFail();

        return GlMappingProfile::updateOrCreate(
            ['entity_id' => $entity->id, 'is_default' => true],
            [
                'name' => 'Default',
                'description' => 'Mapping default dari script Python original',
                'created_by' => 'system',
            ]
        );
    }

    private function bulkInsert(GlMappingProfile $profile, array $mappings): void
    {
        GlAccountMapping::where('profile_id', $profile->id)->delete();

        foreach ($mappings as $idx => $m) {
            $m['profile_id'] = $profile->id;
            $m['order_index'] = $idx + 1;
            $m['is_active'] = true;
            $m['components'] = isset($m['components']) ? json_encode($m['components']) : null;
            $m['match_keywords'] = isset($m['match_keywords']) ? json_encode($m['match_keywords']) : null;
            $m['created_at'] = now();
            $m['updated_at'] = now();
            GlAccountMapping::insert($m);
        }
    }

    private function createStrategyDConfig(GlMappingProfile $profile, array $debitAccounts, array $debitKeywords = []): void
    {
        GlStrategyDConfig::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'debit_accounts' => $debitAccounts,
                'debit_keywords' => $debitKeywords ?: ['pengembalian'],
                'default_dc' => 'Credit',
            ]
        );
    }

    // ==================== DRIVER KMI (Strategy B) ====================
    private function seedDriverKmi(): void
    {
        $profile = $this->createProfile('driver_kmi');

        $mappings = [
            ['mapping_key' => '7000000001', 'account_number' => '7000000001', 'account_name' => 'General & Administration Daily Wages', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '7000000002', 'account_number' => '7000000002', 'account_name' => 'General & Administration Daily Overtime', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '7000000004', 'account_number' => '7000000004', 'account_name' => 'General & Administration Daily Welfare', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '2010000004_TK', 'account_number' => '2010000004', 'account_name' => 'Hutang BPJS TK', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_account_name' => 'hutang bpjs tk'],
            ['mapping_key' => '2010000004_Kes', 'account_number' => '2010000004', 'account_name' => 'Hutang BPJS Kes', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_account_name' => 'hutang bpjs kes'],
            ['mapping_key' => '2050000004', 'account_number' => '2050000004', 'account_name' => 'PPh 21', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_account_name' => 'pph 21'],
            ['mapping_key' => '2010000005_kelalaian', 'account_number' => '2010000005', 'account_name' => 'Potongan kelalaian', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_account_name' => 'potongan kelalaian'],
            ['mapping_key' => '2010000005_lain', 'account_number' => '2010000005', 'account_name' => 'potongan lain-lain', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_account_name' => 'potongan lain-lain'],
            ['mapping_key' => '2010000005_denda', 'account_number' => '2010000005', 'account_name' => 'potongan denda', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_account_name' => 'potongan denda'],
            ['mapping_key' => '2010000005_koperasi', 'account_number' => '2010000005', 'account_name' => 'potongan koperasi', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_account_name' => 'potongan koperasi'],
            ['mapping_key' => '2010000005_indisipliner', 'account_number' => '2010000005', 'account_name' => 'potongan indisipliner', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_account_name' => 'potongan indisipliner'],
            ['mapping_key' => '2010000005_denda_sakit', 'account_number' => '2010000005', 'account_name' => 'potongan denda sakit', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_account_name' => 'potongan denda sakit'],
            ['mapping_key' => '2010000005_denda_terlambat', 'account_number' => '2010000005', 'account_name' => 'potongan denda terlambat', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_account_name' => 'potongan denda terlambat'],
            ['mapping_key' => '2010000005_lainnya', 'account_number' => '2010000005', 'account_name' => 'potongan lainnya', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_account_name' => 'potongan lainnya'],
        ];

        $this->bulkInsert($profile, $mappings);
    }

    // ==================== KMI2 (Strategy B) ====================
    private function seedKmi2(): void
    {
        $profile = $this->createProfile('kmi2');

        $mappings = [
            ['mapping_key' => '5204000005_OUTSOURCING', 'account_number' => '5204000005', 'account_name' => 'Outsourcing Wage', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false, 'match_keywords' => ['outsourcing', 'wage']],
            ['mapping_key' => '5204000008', 'account_number' => '5204000008', 'account_name' => 'BPJS Employee', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '5204000006', 'account_number' => '5204000006', 'account_name' => 'Overtime Outsourcing KMI 2', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '5204000005_DEDUCTION', 'account_number' => '5204000005', 'account_name' => 'Deduction', 'account_type' => 'Cost center', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false, 'match_keywords' => ['deduction']],
            ['mapping_key' => '2010000004_TK', 'account_number' => '2010000004', 'account_name' => 'Hutang BPJS TK', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_keywords' => ['tk']],
            ['mapping_key' => '2010000004_KES', 'account_number' => '2010000004', 'account_name' => 'Hutang BPJS Kes', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_keywords' => ['kes']],
            ['mapping_key' => '2010000005_INDISIPLINER', 'account_number' => '2010000005', 'account_name' => 'Potongan Indisipliner', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_keywords' => ['potongan indisipliner']],
            ['mapping_key' => '2010000005_LAIN_LAIN', 'account_number' => '2010000005', 'account_name' => 'Potongan Lain-lain', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_keywords' => ['lain-lain']],
            ['mapping_key' => '2010000005_LAINNYA', 'account_number' => '2010000005', 'account_name' => 'Potongan Lainnya', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_keywords' => ['lainnya']],
            ['mapping_key' => '2010000005_KELALAIAN', 'account_number' => '2010000005', 'account_name' => 'Potongan Kelalaian', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_keywords' => ['kelalaian']],
            ['mapping_key' => '2010000005_KOPERASI', 'account_number' => '2010000005', 'account_name' => 'Potongan Koperasi', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_keywords' => ['koperasi']],
            ['mapping_key' => '2010000005_DENDA_SAKIT', 'account_number' => '2010000005', 'account_name' => 'Potongan Denda Sakit', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_keywords' => ['denda sakit']],
            ['mapping_key' => '2010000005_DENDA_INDISIPLINER', 'account_number' => '2010000005', 'account_name' => 'Denda Indisipliner', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_keywords' => ['denda indisipliner']],
            ['mapping_key' => '2010000005_DENDA', 'account_number' => '2010000005', 'account_name' => 'Potongan Denda', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_keywords' => ['potongan denda']],
            ['mapping_key' => '2010000005_DENDA_TERLAMBAT', 'account_number' => '2010000005', 'account_name' => 'Potongan Denda Terlambat', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_keywords' => ['denda terlambat']],
            ['mapping_key' => '2010000005_KETERLAMBATAN', 'account_number' => '2010000005', 'account_name' => 'Potongan Keterlambatan', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_keywords' => ['keterlambatan']],
            ['mapping_key' => '2010000005_TOOLS', 'account_number' => '2010000005', 'account_name' => 'Jaminan Tool', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'match_keywords' => ['jaminan tool', 'tools']],
            ['mapping_key' => '2010000006', 'account_number' => '2010000006', 'account_name' => 'Uang Pengembalian Jaminan Tool', 'account_type' => 'Aggregate', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
            ['mapping_key' => '2050000004', 'account_number' => '2050000004', 'account_name' => 'PPh 21', 'account_type' => 'Cost center', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
        ];

        $this->bulkInsert($profile, $mappings);
    }

    // ==================== KMI1 (Strategy E - Mapping ordered) ====================
    private function seedKmi1(): void
    {
        $profile = $this->createProfile('kmi1');

        $mappings = [
            ['mapping_key' => '5204000005_OUTSOURCING', 'account_number' => '5204000005', 'account_name' => 'Outsourcing Wage', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false, 'match_keywords' => ['outsourcing', 'wage']],
            ['mapping_key' => '5204000005_DEDUCTION', 'account_number' => '5204000005', 'account_name' => 'Deduction', 'account_type' => 'Cost center', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false, 'match_keywords' => ['deduction']],
            ['mapping_key' => '5204000008', 'account_number' => '5204000008', 'account_name' => 'BPJS Employee', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '5204000006', 'account_number' => '5204000006', 'account_name' => 'Overtime Wage', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '2010000004', 'account_number' => '2010000004', 'account_name' => 'Hutang BPJS', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
            ['mapping_key' => '2010000005', 'account_number' => '2010000005', 'account_name' => 'Uang Titipan Jaminan', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
            ['mapping_key' => '2010000006', 'account_number' => '2010000006', 'account_name' => 'Uang Pengembalian Jaminan J', 'account_type' => 'Aggregate', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
            ['mapping_key' => '2050000004', 'account_number' => '2050000004', 'account_name' => 'PPh 21', 'account_type' => 'Cost center', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
        ];

        $this->bulkInsert($profile, $mappings);
    }

    // ==================== PEMBANTU KMI (Strategy F - Per-detail include-zero) ====================
    private function seedPembantuKmi(): void
    {
        $profile = $this->createProfile('pembantu_kmi');

        $mappings = [
            ['mapping_key' => '7000000001', 'account_number' => '7000000001', 'account_name' => 'General & Administration Daily Wages', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '2010000004', 'account_number' => '2010000004', 'account_name' => 'Hutang BPJS TK - G.Affair', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
            ['mapping_key' => '2010000005', 'account_number' => '2010000005', 'account_name' => 'Uang Titipan Jaminan Kelalaian', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
            ['mapping_key' => '2050000004', 'account_number' => '2050000004', 'account_name' => 'PPh21', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
        ];

        $this->bulkInsert($profile, $mappings);
    }

    // ==================== KARYAWAN HARIAN LEPAS (Strategy D) ====================
    private function seedKaryawanHarianLepas(): void
    {
        $profile = $this->createProfile('karyawan_harian_lepas');

        // Strategy D: tidak butuh mapping detail, hanya butuh konfigurasi debit whitelist
        // Tapi tetap simpan info untuk match keperluan validasi/UI
        $mappings = [
            ['mapping_key' => '5204000008', 'account_number' => '5204000008', 'account_name' => 'BPJS Employee', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '7000000001', 'account_number' => '7000000001', 'account_name' => 'General & Administration Daily Wages', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '7000000002', 'account_number' => '7000000002', 'account_name' => 'General & Administration Daily Overtime', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '2010000004', 'account_number' => '2010000004', 'account_name' => 'Hutang BPJS', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
            ['mapping_key' => '2010000005', 'account_number' => '2010000005', 'account_name' => 'Uang Titipan', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
            ['mapping_key' => '5204000005', 'account_number' => '5204000005', 'account_name' => 'Deduction', 'account_type' => 'Cost center', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
        ];

        $this->bulkInsert($profile, $mappings);

        $this->createStrategyDConfig($profile,
            ['5204000008', '7000000001', '7000000002'],
            []
        );
    }

    // ==================== NON STAFF KMI (Strategy D) ====================
    private function seedNonStaffKmi(): void
    {
        $profile = $this->createProfile('non_staff_kmi');

        $mappings = [
            ['mapping_key' => '7000000001', 'account_number' => '7000000001', 'account_name' => 'General & Administration Daily Wages', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '7000000002', 'account_number' => '7000000002', 'account_name' => 'General & Administration Daily Overtime', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '7000000004', 'account_number' => '7000000004', 'account_name' => 'General & Administration Daily Welfare', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '5204000001', 'account_number' => '5204000001', 'account_name' => 'Production Daily Wage', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '5204000002', 'account_number' => '5204000002', 'account_name' => 'Production Daily Overtime', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '5204000004', 'account_number' => '5204000004', 'account_name' => 'Production Daily Welfare', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '2010000006', 'account_number' => '2010000006', 'account_name' => 'Uang Pengembalian Jaminan Tool Staff', 'account_type' => 'Aggregate', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
            ['mapping_key' => '2010000004', 'account_number' => '2010000004', 'account_name' => 'Hutang BPJS', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
            ['mapping_key' => '2010000005', 'account_number' => '2010000005', 'account_name' => 'Potongan', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
            ['mapping_key' => '2050000004', 'account_number' => '2050000004', 'account_name' => 'PPh 21', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
            ['mapping_key' => '5204000005', 'account_number' => '5204000005', 'account_name' => 'Deduction', 'account_type' => 'Cost center', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
        ];

        $this->bulkInsert($profile, $mappings);

        $this->createStrategyDConfig($profile,
            ['7000000001', '7000000002', '7000000004', '5204000001', '5204000002', '5204000004', '2010000006'],
            ['pengembalian']
        );
    }

    // ==================== STAFF KMI (Strategy D) ====================
    private function seedStaffKmi(): void
    {
        $profile = $this->createProfile('staff_kmi');

        $mappings = [
            ['mapping_key' => '7000000005', 'account_number' => '7000000005', 'account_name' => 'General & Administration Staff Salaries', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '7000000006', 'account_number' => '7000000006', 'account_name' => 'General & Administration Staff Overtime', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '7000000008', 'account_number' => '7000000008', 'account_name' => 'General & Administration Staff Welfare', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '5204000009', 'account_number' => '5204000009', 'account_name' => 'Production Staff Salaries', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '5204000010', 'account_number' => '5204000010', 'account_name' => 'Production Staff Overtime', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '5204000012', 'account_number' => '5204000012', 'account_name' => 'Production Staff Welfare', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '2010000006', 'account_number' => '2010000006', 'account_name' => 'Uang Pengembalian Jaminan Tool Staff', 'account_type' => 'Aggregate', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
            ['mapping_key' => '2010000004', 'account_number' => '2010000004', 'account_name' => 'Hutang BPJS', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
            ['mapping_key' => '2010000005', 'account_number' => '2010000005', 'account_name' => 'Potongan', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
            ['mapping_key' => '2050000004', 'account_number' => '2050000004', 'account_name' => 'PPh 21', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
        ];

        $this->bulkInsert($profile, $mappings);

        $this->createStrategyDConfig($profile,
            ['7000000005', '7000000006', '7000000008', '5204000009', '5204000010', '5204000012', '2010000006'],
            ['pengembalian']
        );
    }
}