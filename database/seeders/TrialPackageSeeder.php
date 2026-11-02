<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class TrialPackageSeeder extends Seeder
{
    /**
     * Run the database seeds to update or create Trial Package.
     */
    public function run(): void
    {
        $trialData = [
            'name' => 'Paket Trial Rintisan',
            'category' => 'TRIAL GRATIS',
            'max_doors' => 15,
            'door_label' => 'Hingga 15 Pintu',
            'price' => 0,
            'duration_days' => 14,
            'billing_cycle' => 'trial',
            'badge_text' => 'COBA GRATIS 14 HARI',
            'discount_label' => null,
            'is_featured' => false,
            'description' => 'Nikmati akses penuh seluruh fitur manajemen kos secara gratis selama 14 hari tanpa komitmen.',
            'features' => [
                'Akses Penuh Seluruh Fitur Selama 14 Hari',
                'Kapasitas hingga 15 Pintu / Kamar',
                'Pencatatan Pemasukan & Pengeluaran',
                'Kuitansi Tagihan Digital via WhatsApp',
                'Tanpa Kartu Kredit & Tanpa Risiko',
            ],
            'is_active' => true,
        ];

        // Opsi A: Update record ID 1 jika ingin menggantikan paket pertama
        $firstPackage = Package::find(1);
        if ($firstPackage) {
            $firstPackage->update($trialData);
        } else {
            // Opsi B: Buat baru jika belum ada
            Package::create($trialData);
        }
    }
}
