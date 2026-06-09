<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            GlEntitySeeder::class,
            GlAccountPrefixSeeder::class,
            GlCostCenterSeeder::class,
            GlSemarangMappingSeeder::class,
            GlSurabayaMappingSeeder::class,
        ]);
    }
}