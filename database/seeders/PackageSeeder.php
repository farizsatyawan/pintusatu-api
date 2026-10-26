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
                'name' => 'Paket Rintisan',
                'category' => 'KOST MANDIRI',
                'max_doors' => 10,
                'door_label' => 'Hingga 10 Pintu',
                'price' => 79000,
                'duration_days' => 30,
                'billing_cycle' => 'monthly',
                'badge_text' => null,
                'discount_label' => null,
                'is_featured' => false,
                'description' => 'Pilihan ideal bagi pemilik kos pemula atau pengelola indekos mandiri dengan skala kecil.',
                'features' => [
                    'Kapasitas hingga 10 Pintu / Kamar',
                    'Pencatatan Pemasukan & Pengeluaran',
                    'Kuitansi Tagihan Digital via WhatsApp',
                    'Laporan Rekap Bulanan Sederhana',
                    'Dukungan Komunitas & Email Support',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Paket Juragan Pro',
                'category' => 'MULTI-CABANG',
                'max_doors' => 50,
                'door_label' => 'Hingga 50 Pintu',
                'price' => 229000,
                'duration_days' => 30,
                'billing_cycle' => 'monthly',
                'badge_text' => 'PALING BANYAK DIPILIH',
                'discount_label' => 'Hemat 20%',
                'is_featured' => true,
                'description' => 'Solusi terlengkap dan terpopuler untuk juragan kos yang mengelola beberapa properti secara profesional.',
                'features' => [
                    'Kapasitas hingga 50 Pintu / Kamar',
                    'Semua Fitur Paket Rintisan',
                    'Manajemen Multi-Cabang / Banyak Lokasi',
                    'Pengingat Jatuh Tempo Otomatis via WhatsApp',
                    'Akses Multi-Staff & Hak Akses Pengelola',
                    'Laporan Okupansi & Laba Rugi Real-Time',
                    'Export Data Keuangan ke Excel & PDF',
                    'Prioritas Customer Support 24/7',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Paket Korporat',
                'category' => 'SKALA BESAR',
                'max_doors' => -1,
                'door_label' => 'Unlimited Pintu',
                'price' => 499000,
                'duration_days' => 30,
                'billing_cycle' => 'monthly',
                'badge_text' => null,
                'discount_label' => null,
                'is_featured' => false,
                'description' => 'Didesain khusus untuk operator co-living, jaringan asrama, atau pengusaha kos skala besar.',
                'features' => [
                    'Kapasitas Pintu & Cabang Tanpa Batas (Unlimited)',
                    'Semua Fitur Paket Juragan Pro',
                    'Integrasi Smart Lock & IoT Kamar (Add-on)',
                    'Integrasi Payment Gateway Otomatis',
                    'Dedicated Account Manager',
                    'Akses Webhook & Integrasi API Kustom',
                    'Jaminan SLA Uptime 99.9%',
                    'Sesi Onboarding & Training Tim Privat',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($packages as $pkg) {
            Package::updateOrCreate(
                ['name' => $pkg['name']],
                $pkg
            );
        }
    }
}
