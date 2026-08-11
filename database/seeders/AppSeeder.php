<?php

namespace Database\Seeders;

use App\Core\Modules\Apps\Models\AppModel;
use Illuminate\Database\Seeder;

class AppSeeder extends Seeder
{
    /**
     * Seed katalog aplikasi megakomsel.com (PRD Sprint 2).
     */
    public function run(): void
    {
        $apps = [
            [
                'slug' => 'absensi',
                'name' => 'Absensi',
                'description' => 'Aplikasi manajemen kehadiran karyawan: absen GPS, izin, rekap (produk pertama).',
                'price_monthly' => 35000,
                'status' => 'available',
            ],
            [
                'slug' => 'toyaa',
                'name' => 'Toyaa',
                'description' => 'Aplikasi pencatatan air & meteran untuk PDAM kecil / pengelola air.',
                'price_monthly' => 50000,
                'status' => 'available',
            ],
            [
                'slug' => 'kasirumkm',
                'name' => 'Kasir UMKM',
                'description' => 'Aplikasi kasir & manajemen stok untuk UMKM.',
                'price_monthly' => 25000,
                'status' => 'coming_soon',
            ],
            [
                'slug' => 'laundry',
                'name' => 'Laundry',
                'description' => 'Aplikasi manajemen laundry: order, status, laporan.',
                'price_monthly' => 25000,
                'status' => 'coming_soon',
            ],
        ];

        foreach ($apps as $app) {
            AppModel::updateOrCreate(['slug' => $app['slug']], $app);
        }
    }
}
