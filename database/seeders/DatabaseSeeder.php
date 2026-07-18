<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MataPelajaranSeeder::class,
            // Tambahkan seeder lain di sini jika perlu (misal SoalSeeder untuk data dummy)
        ]);

        User::query()->delete();

        User::create([
            'name' => 'Admin Aksara',
            'email' => 'makivgg@gmail.com',
            'password' => Hash::make('Makiv2008.'),
            'email_verified_at' => now(),
        ]);
    }
}
