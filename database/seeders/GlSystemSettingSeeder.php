<?php

namespace Database\Seeders;

use App\Models\GlSystemSetting;
use Illuminate\Database\Seeder;

class GlSystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        // PIN untuk Reset Center
        GlSystemSetting::setValue(
            'reset_center_pin',
            '2026',
            'PIN untuk akses Reset Center. Ganti via tinker kalau perlu.'
        );
    }
}