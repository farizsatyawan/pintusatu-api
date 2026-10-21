<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Paket Bulanan',
                'slug' => 'monthly',
                'price' => 150000,
                'duration_days' => 30,
                'description' => 'Akses penuh seluruh fitur manajemen kos selama 30 hari.',
                'is_active' => true,
            ],
            [
                'name' => 'Paket Tahunan',
                'slug' => 'yearly',
                'price' => 1500000,
                'duration_days' => 365,
                'description' => 'Akses penuh seluruh fitur manajemen kos selama 1 tahun (hemat 2 bulan).',
                'is_active' => true,
            ],
        ];

        foreach ($packages as $pkg) {
            Package::updateOrCreate(
                ['slug' => $pkg['slug']],
                $pkg
            );
        }
    }
}
