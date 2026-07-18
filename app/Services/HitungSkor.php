<?php

namespace App\Services;

use App\Models\AdminSoal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HitungSkor
{
    /**
     * Hitung skor semua peserta yang sudah selesai.
     * Hasilnya di-upsert ke tabel nilai_details di peserta_db, lewat
     * koneksi 'peserta_db_scoring' yang izinnya dibatasi cuma ke
     * tabel itu saja (lihat config/database.php).
     */
    public function hitungSemuaPeserta(): Collection
    {
        $hasil = collect();

        DB::connection('peserta_db')
            ->table('pesertas')
            ->select('id')
            ->where('status', 'selesai')
            ->orderBy('id')
            ->chunkById(100, function ($pesertas) use ($hasil) {
                foreach ($pesertas as $peserta) {
                    $hasil->push($this->hitungSatuPeserta($peserta->id));
                }
            });

        return $hasil;
    }

    /**
     * Hitung skor satu peserta berdasarkan ID.
     * Return array hasil yang siap ditampilkan di tabel rekap.
     */
    public function hitungSatuPeserta(int $pesertaId): array
    {
        // ── Data peserta dari peserta_db ───────────────────────────
        $peserta = DB::connection('peserta_db')
            ->table('pesertas')
            ->where('id', $pesertaId)
            ->first();

        if (! $peserta) {
            return ['error' => "Peserta ID {$pesertaId} tidak ditemukan."];
        }

        // ── Mapel yang dikerjakan peserta ini ──────────────────────
        $pilihanIds = DB::connection('peserta_db')
            ->table('peserta_mata_pelajaran')
            ->where('peserta_id', $pesertaId)
            ->pluck('mata_pelajaran_id');

        $wajibIds = Cache::store('file')->remember('mata_pelajaran:wajib_ids', now()->addHour(), function () {
            return DB::connection('peserta_db')
                ->table('mata_pelajarans')
                ->where('tipe', 'wajib')
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->values()
                ->all();
        });

        $allMapelIds = collect($pilihanIds)
            ->merge(collect($wajibIds)->flatten())
            ->flatten()
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        // ── Soal + kunci dari admin DB ─────────────────────────────
        $soals = AdminSoal::whereIn('mata_pelajaran_id', $allMapelIds->all())
            ->get()
            ->keyBy('id');

        // ── Jawaban peserta dari peserta_db ────────────────────────
        $jawabans = DB::connection('peserta_db')
            ->table('jawabans')
            ->where('peserta_id', $pesertaId)
            ->get()
            ->keyBy('soal_id');

        // ── Hitung per mapel ───────────────────────────────────────
        $nilaiPerMapel  = [];
        $totalBenar     = 0;

        foreach ($allMapelIds as $mapelId) {
            $soalMapel = $soals->where('mata_pelajaran_id', $mapelId);
            $benar = $salah = $kosong = 0;

            foreach ($soalMapel as $soal) {
                $jawaban = $jawabans->get($soal->id)?->jawaban;

                if (! $jawaban) {
                    $kosong++;
                } elseif (strtoupper($jawaban) === strtoupper($soal->kunci_jawaban)) {
                    $benar++;
                } else {
                    $salah++;
                }
            }

            // TKA: 1 poin per benar, salah & kosong = 0 (tidak minus)
            $skor = $benar * 1;
            $nilaiPerMapel[$mapelId] = compact('benar', 'salah', 'kosong', 'skor');
            $totalBenar += $benar;
        }

        // ── Simpan ke nilai_details ─────────────────────────────────
        // PENTING: pakai koneksi 'peserta_db_scoring' (bukan 'peserta_db'
        // yang readonly). User MySQL di koneksi ini cuma dikasih izin
        // INSERT/UPDATE ke tabel nilai_details, tidak ke tabel lain.
        foreach ($nilaiPerMapel as $mapelId => $n) {
            DB::connection('peserta_db_scoring')
                ->table('nilai_details')
                ->upsert(
                    [
                        'peserta_id'       => $pesertaId,
                        'mata_pelajaran_id' => $mapelId,
                        'benar'            => $n['benar'],
                        'salah'            => $n['salah'],
                        'kosong'           => $n['kosong'],
                        'skor'             => $n['skor'],
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ],
                    ['peserta_id', 'mata_pelajaran_id'],
                    ['benar', 'salah', 'kosong', 'skor', 'updated_at']
                );
        }

        Log::info("Skor dihitung untuk peserta {$peserta->no_ujian}", [
            'total_benar' => $totalBenar,
        ]);

        return [
            'peserta_id'      => $pesertaId,
            'nama'            => $peserta->nama,
            'nama_sekolah'    => $peserta->nama_sekolah,
            'no_ujian'        => $peserta->no_ujian,
            'total_benar'     => $totalBenar,
            'skor_total'      => $totalBenar,
            'nilai_per_mapel' => $nilaiPerMapel,
        ];
    }
}
