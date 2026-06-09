<?php

namespace Database\Seeders;

use App\Models\GlEntity;
use Illuminate\Database\Seeder;

class GlEntitySeeder extends Seeder
{
    public function run(): void
    {
        $entities = [
            // ============ SEMARANG (6 entity, branch 21090) ============
            [
                'code' => 'cs_semarang',
                'name' => 'CS Semarang',
                'region' => 'semarang',
                'ledger_code' => 'G_L CS Semarang',
                'branch_id' => '21090',
                'ledger_id_strategy' => 'single',
                'ledger_id_list' => [900],
                'doc_header_template' => 'PAYROLL CS SEMARANG {MONTH} {YEAR}',
                'output_filename_template' => 'Upload_GL_CS_SEMARANG_{YEAR}_{MONTH}.xlsx',
                'extraction_strategy' => 'A',
            ],
            [
                'code' => 'driver_semarang',
                'name' => 'Driver Semarang',
                'region' => 'semarang',
                'ledger_code' => 'G_L Driver Semarang',
                'branch_id' => '21090',
                'ledger_id_strategy' => 'single',
                'ledger_id_list' => [900],
                'doc_header_template' => 'PAYROLL DRIVER SEMARANG {MONTH} {YEAR}',
                'output_filename_template' => 'Upload_GL_DRIVER_SEMARANG_{YEAR}_{MONTH}.xlsx',
                'extraction_strategy' => 'A',
            ],
            [
                'code' => 'pembantu_semarang',
                'name' => 'Pembantu Semarang',
                'region' => 'semarang',
                'ledger_code' => 'G_L Pembantu Semarang',
                'branch_id' => '21090',
                'ledger_id_strategy' => 'single',
                'ledger_id_list' => [900],
                'doc_header_template' => 'PAYROLL PEMBANTU SEMARANG {MONTH} {YEAR}',
                'output_filename_template' => 'Upload_GL_PEMBANTU_SEMARANG_{YEAR}_{MONTH}.xlsx',
                'extraction_strategy' => 'A',
            ],
            [
                'code' => 'non_staff_semarang',
                'name' => 'Karyawan Non Staff Semarang',
                'region' => 'semarang',
                'ledger_code' => 'G_L Karyawan Non Staff Semarang',
                'branch_id' => '21090',
                'ledger_id_strategy' => 'single',
                'ledger_id_list' => [900],
                'doc_header_template' => 'PAYROLL NON STAFF SEMARANG {MONTH} {YEAR}',
                'output_filename_template' => 'Upload_GL_NON_STAFF_SEMARANG_{YEAR}_{MONTH}.xlsx',
                'extraction_strategy' => 'B',
            ],
            [
                'code' => 'staff_semarang',
                'name' => 'Staff Semarang',
                'region' => 'semarang',
                'ledger_code' => 'G_L Staff Semarang',
                'branch_id' => '21090',
                'ledger_id_strategy' => 'single',
                'ledger_id_list' => [900],
                'doc_header_template' => 'PAYROLL STAFF SEMARANG {MONTH} {YEAR}',
                'output_filename_template' => 'Upload_GL_STAFF_SEMARANG_{YEAR}_{MONTH}.xlsx',
                'extraction_strategy' => 'B',
            ],
            [
                'code' => 'produksi_semarang',
                'name' => 'Karyawan Produksi Semarang',
                'region' => 'semarang',
                'ledger_code' => 'G_L Karyawan Produksi Semarang',
                'branch_id' => '21090',
                'ledger_id_strategy' => 'single',
                'ledger_id_list' => [900],
                'doc_header_template' => 'PAYROLL KARYAWAN PRODUKSI SEMARANG {MONTH} {YEAR}',
                'output_filename_template' => 'Upload_GL_KARYAWAN_PRODUKSI_SEMARANG_{YEAR}_{MONTH}.xlsx',
                'extraction_strategy' => 'C',
            ],

            // ============ SURABAYA (7 entity) ============
            [
                'code' => 'driver_kmi',
                'name' => 'Driver KMI',
                'region' => 'surabaya',
                'ledger_code' => 'G_L Driver',
                'branch_id' => '21089',
                'ledger_id_strategy' => 'single',
                'ledger_id_list' => [900],
                'doc_header_template' => 'PAYROLL DRIVER KMI {MONTH} {YEAR}',
                'output_filename_template' => 'Upload_GL_DRIVER_KMI_{YEAR}_{MONTH}.xlsx',
                'extraction_strategy' => 'B',
            ],
            [
                'code' => 'kmi2',
                'name' => 'KMI 2 SP Outsourcing',
                'region' => 'surabaya',
                'ledger_code' => 'G_L KMI 2 SP (Outsourcing)',
                'branch_id' => '21089',
                'ledger_id_strategy' => 'multi_try',
                'ledger_id_list' => [900, 901, 902, 903, 904, 905],
                'doc_header_template' => 'PAYROLL KMI2 {MONTH} {YEAR}',
                'output_filename_template' => 'Upload_GL_KMI2_{YEAR}_{MONTH}.xlsx',
                'extraction_strategy' => 'B',
            ],
            [
                'code' => 'kmi1',
                'name' => 'KMI 1 SGM Outsourcing',
                'region' => 'surabaya',
                'ledger_code' => 'G_L KMI 1 SGM (Outsourcing)',
                'branch_id' => '21087',
                'ledger_id_strategy' => 'multi_try',
                'ledger_id_list' => [900, 901, 902, 903],
                'doc_header_template' => 'PAYROLL KMI1 {MONTH} {YEAR}',
                'output_filename_template' => 'Upload_GL_KMI1_{YEAR}_{MONTH}.xlsx',
                'extraction_strategy' => 'E',
            ],
            [
                'code' => 'pembantu_kmi',
                'name' => 'GA Pembantu KMI',
                'region' => 'surabaya',
                'ledger_code' => 'G_L GA Pembantu',
                'branch_id' => '21089',
                'ledger_id_strategy' => 'single',
                'ledger_id_list' => [900],
                'doc_header_template' => 'PAYROLL GA PEMBANTU KMI {MONTH} {YEAR}',
                'output_filename_template' => 'Upload_GL_PEMBANTU_KMI_{YEAR}_{MONTH}.xlsx',
                'extraction_strategy' => 'F',
            ],
            [
                'code' => 'karyawan_harian_lepas',
                'name' => 'Karyawan Harian Lepas KMI',
                'region' => 'surabaya',
                'ledger_code' => 'G_L Karyawan Harian lepas',
                'branch_id' => '21089',
                'ledger_id_strategy' => 'single',
                'ledger_id_list' => [900],
                'doc_header_template' => 'PAYROLL KARYAWAN HARIAN LEPAS KMI {MONTH} {YEAR}',
                'output_filename_template' => 'Upload_GL_KARYAWAN_HARIAN_LEPAS_KMI_{YEAR}_{MONTH}.xlsx',
                'extraction_strategy' => 'D',
            ],
            [
                'code' => 'non_staff_kmi',
                'name' => 'Non Staff KMI',
                'region' => 'surabaya',
                'ledger_code' => 'G_L Non Staf KMI',
                'branch_id' => '21089',
                'ledger_id_strategy' => 'multi_try',
                'ledger_id_list' => [900, 901, 902, 903],
                'doc_header_template' => 'PAYROLL NON STAFF KMI {MONTH} {YEAR}',
                'output_filename_template' => 'Upload_GL_NON_STAFF_KMI_{YEAR}_{MONTH}.xlsx',
                'extraction_strategy' => 'D',
            ],
            [
                'code' => 'staff_kmi',
                'name' => 'Staff KMI',
                'region' => 'surabaya',
                'ledger_code' => 'G_L Staff KMI',
                'branch_id' => '21089',
                'ledger_id_strategy' => 'multi_try',
                'ledger_id_list' => [900, 901, 902, 903],
                'doc_header_template' => 'PAYROLL STAFF KMI {MONTH} {YEAR}',
                'output_filename_template' => 'Upload_GL_STAFF_KMI_{YEAR}_{MONTH}.xlsx',
                'extraction_strategy' => 'D',
            ],
        ];

        foreach ($entities as $data) {
            GlEntity::updateOrCreate(['code' => $data['code']], $data);
        }

        $this->command->info('Seeded ' . count($entities) . ' entities (6 Semarang + 7 Surabaya)');
    }
}