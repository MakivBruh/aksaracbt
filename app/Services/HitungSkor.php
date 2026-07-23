<?php

namespace App\Services;

use App\Models\AdminSoal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HitungSkor
{
    public function __construct(private QuestionScorer $scorer) {}

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
        $soals = AdminSoal::with('items')->whereIn('mata_pelajaran_id', $allMapelIds->all())
            ->get()
            ->keyBy('id');

        $mapelTypes = DB::connection('peserta_db')->table('mata_pelajarans')
            ->whereIn('id', $allMapelIds)
            ->pluck('tipe', 'id');

        // ── Jawaban peserta dari peserta_db ────────────────────────
        $jawabans = DB::connection('peserta_db')
            ->table('jawabans')
            ->where('peserta_id', $pesertaId)
            ->get()
            ->keyBy('soal_id');

        // ── Hitung per mapel ───────────────────────────────────────
        $nilaiPerMapel  = [];
        $totalBenar     = 0;
        $totalPoinMentah = '0.000000';

        foreach ($allMapelIds as $mapelId) {
            $soalMapel = $soals->where('mata_pelajaran_id', $mapelId);
            $benar = $salah = $kosong = 0;
            $poinMapel = '0.000000';

            foreach ($soalMapel as $soal) {
                $jawabanRow = $jawabans->get($soal->id);
                $tipeSoal = $soal->tipe_soal ?: 'pilihan_ganda';
                $jawaban = $tipeSoal === 'pilihan_ganda'
                    ? $jawabanRow?->jawaban
                    : json_decode((string) ($jawabanRow?->jawaban_data ?? 'null'), true);
                $nilaiMaksimum = (string) ($soal->nilai_maksimum ?: ($mapelTypes[$mapelId] === 'pilihan' ? '10' : '5'));
                $hasilSoal = $this->scorer->score(
                    $tipeSoal,
                    $nilaiMaksimum,
                    $jawaban,
                    $soal->kunci_jawaban,
                    $soal->items->map(fn($item) => [
                        'id' => $item->id,
                        'is_correct' => $item->is_correct,
                        'correct_value' => $item->correct_value,
                    ])->all(),
                );

                if (! $hasilSoal['answered']) {
                    $kosong++;
                } elseif ($hasilSoal['fully_correct']) {
                    $benar++;
                } else {
                    $salah++;
                }

                $poinMapel = $this->scorer->add($poinMapel, $hasilSoal['score']);

                DB::connection('peserta_db_scoring')->table('nilai_soal_details')->upsert([
                    [
                        'peserta_id' => $pesertaId,
                        'soal_id' => $soal->id,
                        'poin_diperoleh' => $hasilSoal['score'],
                        'poin_maksimum' => $nilaiMaksimum,
                        'sub_item_benar' => $hasilSoal['correct_items'],
                        'jumlah_sub_item' => max(1, $hasilSoal['item_count']),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ], ['peserta_id', 'soal_id'], [
                    'poin_diperoleh', 'poin_maksimum', 'sub_item_benar', 'jumlah_sub_item', 'updated_at',
                ]);
            }

            $skor = round((float) $poinMapel, 2);
            $nilaiPerMapel[$mapelId] = compact('benar', 'salah', 'kosong', 'skor', 'poinMapel');
            $totalBenar += $benar;
            $totalPoinMentah = $this->scorer->add($totalPoinMentah, $poinMapel);
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
                        'poin_mentah'      => $n['poinMapel'],
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ],
                    ['peserta_id', 'mata_pelajaran_id'],
                    ['benar', 'salah', 'kosong', 'skor', 'poin_mentah', 'updated_at']
                );
        }

        $nilaiAkhir = $this->scorer->finalScore($totalPoinMentah);
        DB::connection('peserta_db_scoring')->table('nilai_totals')->upsert([
            [
                'peserta_id' => $pesertaId,
                'poin_mentah' => $totalPoinMentah,
                'nilai_akhir' => $nilaiAkhir,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['peserta_id'], ['poin_mentah', 'nilai_akhir', 'updated_at']);

        Log::info("Skor dihitung untuk peserta {$peserta->no_ujian}", [
            'total_benar' => $totalBenar,
        ]);

        return [
            'peserta_id'      => $pesertaId,
            'nama'            => $peserta->nama,
            'nama_sekolah'    => $peserta->nama_sekolah,
            'no_ujian'        => $peserta->no_ujian,
            'total_benar'     => $totalBenar,
            'skor_total'      => $totalPoinMentah,
            'total_poin_mentah' => $totalPoinMentah,
            'nilai_akhir'     => $nilaiAkhir,
            'nilai_per_mapel' => $nilaiPerMapel,
        ];
    }
}
