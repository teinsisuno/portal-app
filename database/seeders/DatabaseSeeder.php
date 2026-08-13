<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Superadmin platform (kredensial dev, lihat AGENTS.md).
        // Catatan: pakai User::create + Hash langsung, BUKAN factory —
        // image produksi di-install tanpa fakerphp/faker (composer --no-dev),
        // jadi User::factory() error "Call to undefined function fake()".
        User::create([
            'name' => 'Admin Urano',
            'email' => 'admin@uranop.com',
            'password' => Hash::make('admin123456'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->call([
            AppSeeder::class,
        ]);
    }
}
