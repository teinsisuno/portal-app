<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Superadmin platform (kredensial dev, lihat AGENTS.md).
        User::factory()->create([
            'name' => 'Admin Urano',
            'email' => 'admin@uranop.com',
            'password' => 'admin123456',
            'is_admin' => true,
        ]);

        $this->call([
            AppSeeder::class,
        ]);
    }
}
