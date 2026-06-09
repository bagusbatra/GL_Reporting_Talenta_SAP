<?php

namespace Database\Seeders;

use App\Models\GlAccountMapping;
use App\Models\GlEntity;
use App\Models\GlMappingProfile;
use App\Models\GlStrategyDConfig;
use Illuminate\Database\Seeder;

class GlSemarangMappingSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCsSemarang();
        $this->seedDriverSemarang();
        $this->seedPembantuSemarang();
        $this->seedNonStaffSemarang();
        $this->seedStaffSemarang();
        $this->seedProduksiSemarang();

        $this->command->info('Seeded default mappings for 6 Semarang entities');
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
        // Hapus mapping lama untuk profile ini (clean re-seed)
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

    // ==================== CS SEMARANG (Strategy A) ====================
    private function seedCsSemarang(): void
    {
        $profile = $this->createProfile('cs_semarang');

        $mappings = [
            ['mapping_key' => '7000000002', 'account_number' => '7000000002', 'account_name' => 'Overtime CS Semarang', 'account_type' => 'Aggregate', 'transaction_value' => 'Debit', 'cost_center' => '1094020002', 'profit_center' => null, 'use_profit_center' => false, 'components' => ['Total Overtime CS']],
            ['mapping_key' => '7000000001', 'account_number' => '7000000001', 'account_name' => 'Gaji CS Semarang', 'account_type' => 'Aggregate', 'transaction_value' => 'Debit', 'cost_center' => '1094020002', 'profit_center' => null, 'use_profit_center' => false, 'components' => ['Gaji Pokok']],
            ['mapping_key' => '7000000004', 'account_number' => '7000000004', 'account_name' => 'Jamsostek', 'account_type' => 'Aggregate', 'transaction_value' => 'Debit', 'cost_center' => '1094020002', 'profit_center' => null, 'use_profit_center' => false, 'components' => ['JKK', 'JKM', 'JHT Company', 'JP Company', 'BPJS K Company']],
            ['mapping_key' => '2010000004', 'account_number' => '2010000004', 'account_name' => 'BPJS', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['JP Employee', 'JHT Employee', 'BPJS K Employee', 'JKK', 'JKM', 'JHT Company', 'JP Company', 'BPJS K Company']],
            ['mapping_key' => '2010000005_DENDA_SAKIT', 'account_number' => '2010000005', 'account_name' => 'Potongan Denda Sakit', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Denda Sakit'], 'match_keywords' => ['denda sakit']],
            ['mapping_key' => '2010000005_INDISIPLINER', 'account_number' => '2010000005', 'account_name' => 'Potongan Indisipliner', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Indisipliner'], 'match_keywords' => ['indisipliner']],
            ['mapping_key' => '2010000005_DENDA', 'account_number' => '2010000005', 'account_name' => 'Potongan Denda', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Denda'], 'match_keywords' => ['potongan denda']],
            ['mapping_key' => '2010000005_LAINNYA', 'account_number' => '2010000005', 'account_name' => 'Potongan Lainnya', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Lainnya'], 'match_keywords' => ['lainnya']],
            ['mapping_key' => '2010000005_DENDA_TERLAMBAT', 'account_number' => '2010000005', 'account_name' => 'Potongan Denda Terlambat', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Denda Terlambat'], 'match_keywords' => ['denda terlambat']],
            ['mapping_key' => '2010000005_LAIN', 'account_number' => '2010000005', 'account_name' => 'Potongan Lain-lain', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Lain-lain'], 'match_keywords' => ['lain-lain']],
            ['mapping_key' => '2010000005_KELALAIAN', 'account_number' => '2010000005', 'account_name' => 'Potongan Kelalaian', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Kelalaian'], 'match_keywords' => ['kelalaian']],
            ['mapping_key' => '2010000005_KOPERASI', 'account_number' => '2010000005', 'account_name' => 'Potongan Koperasi', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Koperasi'], 'match_keywords' => ['koperasi']],
            ['mapping_key' => '2050000004', 'account_number' => '2050000004', 'account_name' => 'PPh 21', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => '1094020002', 'profit_center' => null, 'use_profit_center' => false, 'components' => ['PPh 21'], 'match_keywords' => ['pph']],
        ];

        $this->bulkInsert($profile, $mappings);
    }

    // ==================== DRIVER SEMARANG (Strategy A) ====================
    private function seedDriverSemarang(): void
    {
        $profile = $this->createProfile('driver_semarang');

        $mappings = [
            ['mapping_key' => '7000000002', 'account_number' => '7000000002', 'account_name' => 'Lembur Driver', 'account_type' => 'Aggregate', 'transaction_value' => 'Debit', 'cost_center' => '1042010002', 'profit_center' => null, 'use_profit_center' => false, 'components' => ['Overtime'], 'match_keywords' => ['lembur', 'overtime']],
            ['mapping_key' => '7000000001_TUNJANGAN', 'account_number' => '7000000001', 'account_name' => 'Tunjangan Driver', 'account_type' => 'Aggregate', 'transaction_value' => 'Debit', 'cost_center' => '1042010002', 'profit_center' => null, 'use_profit_center' => false, 'components' => ['Tunjangan Lainnya'], 'match_keywords' => ['tunjangan']],
            ['mapping_key' => '7000000001_UPAH', 'account_number' => '7000000001', 'account_name' => 'Upah Driver', 'account_type' => 'Aggregate', 'transaction_value' => 'Debit', 'cost_center' => '1042010002', 'profit_center' => null, 'use_profit_center' => false, 'components' => ['Upah Harian'], 'match_keywords' => ['upah']],
            ['mapping_key' => '7000000004', 'account_number' => '7000000004', 'account_name' => 'Jamsostek', 'account_type' => 'Aggregate', 'transaction_value' => 'Debit', 'cost_center' => '1042010002', 'profit_center' => null, 'use_profit_center' => false, 'components' => ['JKK', 'JKM', 'JHT Company', 'JP Company', 'BPJS K Company'], 'match_keywords' => ['jamsostek']],
            ['mapping_key' => '2050000004', 'account_number' => '2050000004', 'account_name' => 'PPh 21', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['PPh 21'], 'match_keywords' => ['pph 21']],
            ['mapping_key' => '2010000005_KELALAIAN', 'account_number' => '2010000005', 'account_name' => 'Potongan Kelalaian', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Kelalaian'], 'match_keywords' => ['kelalaian']],
            ['mapping_key' => '2010000005_LAIN', 'account_number' => '2010000005', 'account_name' => 'Potongan Lain-lain', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Lain-lain'], 'match_keywords' => ['lain-lain']],
            ['mapping_key' => '2010000005_KOPERASI', 'account_number' => '2010000005', 'account_name' => 'Potongan Koperasi', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Koperasi'], 'match_keywords' => ['koperasi']],
            ['mapping_key' => '2010000005_KETERLAMBATAN', 'account_number' => '2010000005', 'account_name' => 'Potongan Keterlambatan', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Keterlambatan'], 'match_keywords' => ['keterlambatan']],
            ['mapping_key' => '2010000005_INDISIPLINER', 'account_number' => '2010000005', 'account_name' => 'Potongan Indisipliner', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Indisipliner'], 'match_keywords' => ['indisipliner']],
            ['mapping_key' => '2010000005_DENDA_SAKIT', 'account_number' => '2010000005', 'account_name' => 'Potongan Denda Sakit', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Denda Sakit'], 'match_keywords' => ['denda sakit']],
            ['mapping_key' => '2010000005_DENDA_TERLAMBAT', 'account_number' => '2010000005', 'account_name' => 'Potongan Denda Terlambat', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Denda Terlambat'], 'match_keywords' => ['denda terlambat']],
            ['mapping_key' => '2010000005_DENDA', 'account_number' => '2010000005', 'account_name' => 'Potongan Denda', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Denda'], 'match_keywords' => ['denda']],
            ['mapping_key' => '2010000005_LAINNYA', 'account_number' => '2010000005', 'account_name' => 'Potongan Lainnya', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Lainnya'], 'match_keywords' => ['lainnya']],
            ['mapping_key' => '2010000004', 'account_number' => '2010000004', 'account_name' => 'BPJS', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['JP Employee', 'JHT Employee', 'JKK', 'JKM', 'JHT Company', 'JP Company'], 'match_keywords' => ['bpjs']],
        ];

        $this->bulkInsert($profile, $mappings);
    }

    // ==================== PEMBANTU SEMARANG (Strategy A) ====================
    private function seedPembantuSemarang(): void
    {
        $profile = $this->createProfile('pembantu_semarang');

        $mappings = [
            ['mapping_key' => '7000000001', 'account_number' => '7000000001', 'account_name' => 'Gaji', 'account_type' => 'Aggregate', 'transaction_value' => 'Debit', 'cost_center' => '1094020002', 'profit_center' => null, 'use_profit_center' => false, 'components' => ['Basic Salary'], 'match_keywords' => ['gaji', 'basic salary']],
            ['mapping_key' => '2010000005_KOPERASI', 'account_number' => '2010000005', 'account_name' => 'Potongan Koperasi', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Koperasi'], 'match_keywords' => ['koperasi']],
            ['mapping_key' => '2010000005_LAIN', 'account_number' => '2010000005', 'account_name' => 'Potongan Lain-lain', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Lain-lain'], 'match_keywords' => ['lain-lain']],
            ['mapping_key' => '2010000005_DENDA', 'account_number' => '2010000005', 'account_name' => 'Potongan Denda', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Denda'], 'match_keywords' => ['denda']],
            ['mapping_key' => '2010000005_KETERLAMBATAN', 'account_number' => '2010000005', 'account_name' => 'Potongan Keterlambatan', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Keterlambatan'], 'match_keywords' => ['keterlambatan']],
            ['mapping_key' => '2010000005_INDISIPLINER', 'account_number' => '2010000005', 'account_name' => 'Potongan Indisipliner', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Indisipliner'], 'match_keywords' => ['indisipliner']],
            ['mapping_key' => '2010000005_DENDA_SAKIT', 'account_number' => '2010000005', 'account_name' => 'Potongan Denda Sakit', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Denda Sakit'], 'match_keywords' => ['denda sakit']],
            ['mapping_key' => '2010000005_DENDA_TERLAMBAT', 'account_number' => '2010000005', 'account_name' => 'Potongan Denda Terlambat', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Denda Terlambat'], 'match_keywords' => ['denda terlambat']],
        ];

        $this->bulkInsert($profile, $mappings);
    }

    // ==================== NON STAFF SEMARANG (Strategy B) ====================
    private function seedNonStaffSemarang(): void
    {
        $profile = $this->createProfile('non_staff_semarang');

        $mappings = [
            // Cost center type
            ['mapping_key' => '5204000001', 'account_number' => '5204000001', 'account_name' => 'Gaji Karyawan Non Staff', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false, 'components' => ['Basic Salary', 'Tunjangan Makan/Minum Non', 'Gaji Pokok', 'Salary Hoist Korp']],
            // Aggregate Debit tanpa profit center
            ['mapping_key' => '2010000006', 'account_number' => '2010000006', 'account_name' => 'Uang Pengembalian Jaminan Tools', 'account_type' => 'Aggregate', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false, 'components' => ['Uang Pengembalian Jaminan Tools']],
            // Aggregate Credit dengan profit center
            ['mapping_key' => '2010000005_DENDA_INDISIPLINER', 'account_number' => '2010000005', 'account_name' => 'Denda Indisipliner', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Denda Indisipliner'], 'match_keywords' => ['denda indisipliner']],
            ['mapping_key' => '2010000005_DENDA', 'account_number' => '2010000005', 'account_name' => 'Potongan Denda', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Denda'], 'match_keywords' => ['potongan denda']],
            ['mapping_key' => '2010000005_LAINNYA', 'account_number' => '2010000005', 'account_name' => 'Potongan Lainnya', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Lainnya'], 'match_keywords' => ['lainnya']],
            ['mapping_key' => '2010000005_DENDA_TERLAMBAT', 'account_number' => '2010000005', 'account_name' => 'Potongan Denda Terlambat', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Denda Terlambat'], 'match_keywords' => ['denda terlambat']],
            ['mapping_key' => '2010000005_INDISIPLINER', 'account_number' => '2010000005', 'account_name' => 'Potongan Indisipliner', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Indisipliner'], 'match_keywords' => ['indisipliner']],
            ['mapping_key' => '2010000005_LAIN', 'account_number' => '2010000005', 'account_name' => 'Potongan Lain-lain', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Lain-lain'], 'match_keywords' => ['lain-lain']],
            ['mapping_key' => '2010000005_KELALAIAN', 'account_number' => '2010000005', 'account_name' => 'Potongan Kelalaian', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Kelalaian'], 'match_keywords' => ['kelalaian']],
            ['mapping_key' => '2010000005_LAIN_KERJA', 'account_number' => '2010000005', 'account_name' => 'Potongan Jam Kerja (Min)', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Jam Kerja (Min)'], 'match_keywords' => ['jam kerja']],
            ['mapping_key' => '2010000005_KOREKSI', 'account_number' => '2010000005', 'account_name' => 'Koreksi Gaji (Min)', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Koreksi Gaji (Min)'], 'match_keywords' => ['koreksi gaji']],
            ['mapping_key' => '2010000005_KOPERASI', 'account_number' => '2010000005', 'account_name' => 'Potongan Koperasi', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Koperasi'], 'match_keywords' => ['koperasi']],
            ['mapping_key' => '2010000005_DENDA_SAKIT', 'account_number' => '2010000005', 'account_name' => 'Potongan Denda Sakit', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Denda Sakit'], 'match_keywords' => ['denda sakit']],
            // Cost center type Debit (5204000004)
            ['mapping_key' => '5204000004', 'account_number' => '5204000004', 'account_name' => 'Jamsostek Karyawan Non Staff', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false, 'components' => ['JKK', 'JKM', 'JHT Company', 'JP Company', 'BPJS K Company']],
            ['mapping_key' => '2010000005_JAMINAN_TOOLS', 'account_number' => '2010000005', 'account_name' => 'Jaminan Tools', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Jaminan Tools'], 'match_keywords' => ['jaminan tools']],
            ['mapping_key' => '2010000004_TK', 'account_number' => '2010000004', 'account_name' => 'Hutang BPJS TK Non Staff', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['JP Employee', 'JHT Employee', 'JKK', 'JKM', 'JHT Company', 'JP Company'], 'match_keywords' => ['bpjs tk']],
            ['mapping_key' => '2010000004_KES', 'account_number' => '2010000004', 'account_name' => 'Hutang BPJS Kes Non Staff', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['BPJS K Employee', 'BPJS K Company'], 'match_keywords' => ['bpjs kes', 'bpjs kesehatan']],
            ['mapping_key' => '2050000004', 'account_number' => '2050000004', 'account_name' => 'PPh 21', 'account_type' => 'Cost center', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false, 'components' => ['Total PPh21']],
            ['mapping_key' => '5204000002', 'account_number' => '5204000002', 'account_name' => 'Overtime Karyawan Non Staff', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false, 'components' => ['Lembur Non Staff', 'Overtime']],
        ];

        $this->bulkInsert($profile, $mappings);
    }

    // ==================== STAFF SEMARANG (Strategy B) ====================
    private function seedStaffSemarang(): void
    {
        $profile = $this->createProfile('staff_semarang');

        $mappings = [
            ['mapping_key' => '5204000009', 'account_number' => '5204000009', 'account_name' => 'Gaji Karyawan Staff Prod Semarang', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false, 'components' => ['Basic Salary', 'Tunjangan Jabatan', 'Tunjangan Transport', 'Tunjangan Makan']],
            ['mapping_key' => '5204000012', 'account_number' => '5204000012', 'account_name' => 'Jamsostek Karyawan Staff Prod Semarang', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false, 'components' => ['JKK', 'JKM', 'JHT Company', 'JP Company', 'BPJS K Company']],
            ['mapping_key' => '5204000010', 'account_number' => '5204000010', 'account_name' => 'Overtime Karyawan Staff Prod Semarang', 'account_type' => 'Cost center', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false, 'components' => ['Overtime']],
            ['mapping_key' => '5204000005', 'account_number' => '5204000005', 'account_name' => 'Deduction Pot Kehadiran', 'account_type' => 'Cost center', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false, 'components' => ['Deduction Pot Kehadiran', 'Deduction Pot Koreksi Gaji (Min)', 'Deduction Pot Koreksi Gaji', 'Reduksi Kehadiran', 'Potongan Jam Kerja', 'Kontrak Gaji']],
            ['mapping_key' => '2050000004', 'account_number' => '2050000004', 'account_name' => 'PPh 21', 'account_type' => 'Cost center', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false, 'components' => ['PPh 21', 'Total PPh21', 'PPh 21 ']],
            ['mapping_key' => '2010000004_TK', 'account_number' => '2010000004', 'account_name' => 'Hutang BPJS TK Staff Semarang', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['JP Employee', 'JHT Employee', 'BPJS K Mandiri', 'JKK', 'JKM', 'JHT Company', 'JP Company'], 'match_keywords' => ['bpjs tk']],
            ['mapping_key' => '2010000004_KES', 'account_number' => '2010000004', 'account_name' => 'Hutang BPJS KES Staff Semarang', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['BPJS Kesehatan', 'BPJS KES', 'BPJS K Employee'], 'match_keywords' => ['bpjs kes', 'bpjs kesehatan']],
            ['mapping_key' => '2010000005_DENDA_INDISIPLINER', 'account_number' => '2010000005', 'account_name' => 'Denda Indisipliner', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Denda Indisipliner'], 'match_keywords' => ['denda indisipliner']],
            ['mapping_key' => '2010000005_LAIN', 'account_number' => '2010000005', 'account_name' => 'Potongan Lain-lain', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Lain-lain'], 'match_keywords' => ['lain-lain']],
            ['mapping_key' => '2010000005_KELALAIAN', 'account_number' => '2010000005', 'account_name' => 'Potongan Kelalaian', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Kelalaian'], 'match_keywords' => ['kelalaian']],
            ['mapping_key' => '2010000005_KOPERASI', 'account_number' => '2010000005', 'account_name' => 'Potongan Koperasi', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Koperasi'], 'match_keywords' => ['koperasi']],
            ['mapping_key' => '2010000005_DENDA_SAKIT', 'account_number' => '2010000005', 'account_name' => 'Potongan Denda Sakit', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Denda Sakit'], 'match_keywords' => ['denda sakit']],
            ['mapping_key' => '2010000005_DENDA_TERLAMBAT', 'account_number' => '2010000005', 'account_name' => 'Potongan Denda Terlambat', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Denda Terlambat'], 'match_keywords' => ['denda terlambat']],
            ['mapping_key' => '2010000005_DENDA', 'account_number' => '2010000005', 'account_name' => 'Potongan Denda', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Denda'], 'match_keywords' => ['potongan denda']],
            ['mapping_key' => '2010000005_LAINNYA', 'account_number' => '2010000005', 'account_name' => 'Potongan Lainnya', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Lainnya'], 'match_keywords' => ['lainnya']],
            ['mapping_key' => '2010000005_INDISIPLINER', 'account_number' => '2010000005', 'account_name' => 'Potongan Indisipliner', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Indisipliner'], 'match_keywords' => ['indisipliner']],
            ['mapping_key' => '2010000005_JAMINAN_TOOLS', 'account_number' => '2010000005', 'account_name' => 'Jaminan Tools', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Jaminan Tools'], 'match_keywords' => ['jaminan tools']],
            ['mapping_key' => '2010000005_KETERLAMBATAN', 'account_number' => '2010000005', 'account_name' => 'Potongan Keterlambatan Staff Prod Semarang', 'account_type' => 'Aggregate', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true, 'components' => ['Potongan Keterlamabatan Staff Prod Semarang', 'Potongan Keterlambatan'], 'match_keywords' => ['keterlambatan', 'keterlamabatan']],
        ];

        $this->bulkInsert($profile, $mappings);
    }

    // ==================== PRODUKSI SEMARANG (Strategy C - Individual) ====================
    private function seedProduksiSemarang(): void
    {
        $profile = $this->createProfile('produksi_semarang');

        $mappings = [
            ['mapping_key' => '5204000002', 'account_number' => '5204000002', 'account_name' => 'Overtime Karyawan Produksi', 'account_type' => 'Individual', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '5204000001', 'account_number' => '5204000001', 'account_name' => 'Gaji Karyawan Produksi', 'account_type' => 'Individual', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '2010000006', 'account_number' => '2010000006', 'account_name' => 'Uang Pengembalian Jaminan Tools', 'account_type' => 'Individual', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '2010000005', 'account_number' => '2010000005', 'account_name' => 'Potongan Denda Terlambat', 'account_type' => 'Individual', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
            ['mapping_key' => '2050000004', 'account_number' => '2050000004', 'account_name' => 'PPh 21 Karyawan Produksi', 'account_type' => 'Individual', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
            ['mapping_key' => '2010000004', 'account_number' => '2010000004', 'account_name' => 'Hutang BPJS Karyawan Prod', 'account_type' => 'Individual', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
            ['mapping_key' => '5204000004', 'account_number' => '5204000004', 'account_name' => 'BPJS Karyawan Produksi', 'account_type' => 'Individual', 'transaction_value' => 'Debit', 'cost_center' => null, 'profit_center' => null, 'use_profit_center' => false],
            ['mapping_key' => '5204000005', 'account_number' => '5204000005', 'account_name' => 'Pot Jam Kerja Karyawan Produksi', 'account_type' => 'Individual', 'transaction_value' => 'Credit', 'cost_center' => null, 'profit_center' => '200301', 'use_profit_center' => true],
        ];

        $this->bulkInsert($profile, $mappings);
    }
}