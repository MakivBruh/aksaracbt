<?php

namespace App\Services;

use App\Models\AdminSoal;
use Illuminate\Support\Facades\DB;

/**
 * Soal & kunci jawaban dibuat/diedit panitia di panel admin (satu-satunya
 * tempat kunci jawaban tersimpan). Tapi peserta baca soal dari database
 * peserta yang terpisah. Service ini menyalin ULANG field yang aman
 * ditampilkan ke peserta (semua KECUALI kunci_jawaban) ke tabel `soals`
 * di peserta_db, dengan ID YANG SAMA seperti di admin_db.
 *
 * Kenapa ID harus sama? Karena tabel `jawabans` di peserta_db menyimpan
 * soal_id yang mengacu ke ID itu. Saat HitungSkor.php mencocokkan jawaban
 * peserta dengan kunci_jawaban dari admin-app, pencocokan dilakukan
 * berdasarkan ID yang sama persis di kedua sisi.
 *
 * Koneksi yang dipakai ('peserta_db_soal_sync') SENGAJA dibedakan dari
 * koneksi readonly biasa — user MySQL-nya cuma dikasih izin
 * INSERT/UPDATE/DELETE ke tabel `soals`, tidak ke tabel lain
 * (lihat config/database.php).
 */
class SoalSyncService
{
    private const KOLOM_DISALIN = [
        'mata_pelajaran_id', 'nomor_urut', 'teks_soal', 'gambar_soal',
        'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e',
        'gambar_opsi_a', 'gambar_opsi_b', 'gambar_opsi_c', 'gambar_opsi_d', 'gambar_opsi_e',
        'tipe_opsi',
        // kunci_jawaban SENGAJA TIDAK ada di daftar ini
    ];

    public function sync(AdminSoal $soal): void
    {
        $data = collect($soal->only(self::KOLOM_DISALIN))
            ->put('id', $soal->id)
            ->put('created_at', $soal->created_at)
            ->put('updated_at', now())
            ->toArray();

        DB::connection('peserta_db_soal_sync')
            ->table('soals')
            ->where('id', '!=', $soal->id)
            ->where('mata_pelajaran_id', $soal->mata_pelajaran_id)
            ->where('nomor_urut', $soal->nomor_urut)
            ->delete();

        DB::connection('peserta_db_soal_sync')
            ->table('soals')
            ->updateOrInsert(['id' => $soal->id], $data);
    }

    public function syncSemua(bool $hapusYangTidakAdaDiAdmin = true): int
    {
        $soals = AdminSoal::orderBy('id')->get();

        if ($hapusYangTidakAdaDiAdmin) {
            DB::connection('peserta_db_soal_sync')
                ->table('soals')
                ->whereNotIn('id', $soals->pluck('id')->all() ?: [0])
                ->delete();
        }

        foreach ($soals as $soal) {
            $this->sync($soal);
        }

        return $soals->count();
    }

    public function hapus(int $soalId): void
    {
        DB::connection('peserta_db_soal_sync')
            ->table('soals')
            ->where('id', $soalId)
            ->delete();
    }
}
