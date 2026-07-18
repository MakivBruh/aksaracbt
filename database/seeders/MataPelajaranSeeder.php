<?php

namespace Database\Seeders;

use App\Models\MataPelajaran;
use Illuminate\Database\Seeder;

class MataPelajaranSeeder extends Seeder
{
    public function run(): void
    {
        $mapels = [
            // ── Wajib (semua peserta mendapatkan soal ini) ──────────
            ['nama' => 'Bahasa Indonesia',    'kode' => 'IND', 'tipe' => 'wajib',   'jumlah_soal' => 20],
            ['nama' => 'Bahasa Inggris',      'kode' => 'ING', 'tipe' => 'wajib',   'jumlah_soal' => 20],
            ['nama' => 'Matematika Wajib',    'kode' => 'MAW', 'tipe' => 'wajib',   'jumlah_soal' => 20],

            // ── Pilihan (peserta memilih 2 dari 8) ──────────────────
            ['nama' => 'Matematika Tingkat Lanjut', 'kode' => 'MAL', 'tipe' => 'pilihan', 'jumlah_soal' => 20],
            ['nama' => 'Fisika',              'kode' => 'FIS', 'tipe' => 'pilihan', 'jumlah_soal' => 20],
            ['nama' => 'Kimia',               'kode' => 'KIM', 'tipe' => 'pilihan', 'jumlah_soal' => 20],
            ['nama' => 'Biologi',             'kode' => 'BIO', 'tipe' => 'pilihan', 'jumlah_soal' => 20],
            ['nama' => 'Ekonomi',             'kode' => 'EKO', 'tipe' => 'pilihan', 'jumlah_soal' => 20],
            ['nama' => 'Geografi',            'kode' => 'GEO', 'tipe' => 'pilihan', 'jumlah_soal' => 20],
            ['nama' => 'Sosiologi',           'kode' => 'SOS', 'tipe' => 'pilihan', 'jumlah_soal' => 20],
            ['nama' => 'Sejarah',             'kode' => 'SEJ', 'tipe' => 'pilihan', 'jumlah_soal' => 20],
        ];

        foreach ($mapels as $data) {
            MataPelajaran::updateOrCreate(
                ['kode' => $data['kode']],
                $data
            );
        }

        $this->command->info('Seeded ' . count($mapels) . ' mata pelajaran (3 wajib, 8 pilihan).');
    }
}
