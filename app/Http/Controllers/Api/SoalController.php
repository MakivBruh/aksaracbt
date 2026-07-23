<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Soal;
use App\Services\QuestionContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SoalController extends Controller
{
    public function __construct(private QuestionContent $content) {}

    /**
     * Kembalikan semua soal yang harus dikerjakan peserta ini.
     * Kunci jawaban tidak pernah disertakan di response.
     */
    public function index(Request $request): JsonResponse
    {
        $peserta = $request->user();

        if ($peserta->status === 'selesai') {
            return response()->json(['message' => 'Ujian sudah selesai.'], 403);
        }

        if ($peserta->activeExamSession?->hasEnded()) {
            return response()->json(['message' => 'Waktu ujian sudah habis.'], 403);
        }

        $mapelIds = $peserta->semuaMapelIds()
            ->map(fn($id) => (int) $id)
            ->sort()
            ->values();

        $soalVersion = (string) (
            Soal::whereIn('mata_pelajaran_id', $mapelIds)->max('updated_at')
            ?? Soal::whereIn('mata_pelajaran_id', $mapelIds)->max('created_at')
            ?? 'empty'
        );

        $cachedSoals = Cache::store('file')->remember(
            'peserta_soal_payload:v4:' . md5($mapelIds->implode('-') . '|' . $soalVersion),
            now()->addMinutes(15),
            fn() => $this->buildCachedSoalPayload($mapelIds)
        );

        $token = $request->bearerToken();
        $soals = collect($cachedSoals)->map(fn(array $soal) => [
            'id'                  => $soal['id'],
            'nomor_urut'          => $soal['nomor_urut'],
            'mata_pelajaran'      => $soal['mata_pelajaran'],
            'mata_pelajaran_kode' => $soal['mata_pelajaran_kode'],
            'teks_soal'           => $soal['teks_soal'],
            'tipe_soal'           => $soal['tipe_soal'],
            'option_label_a'      => $soal['option_label_a'],
            'option_label_b'      => $soal['option_label_b'],
            'tabel_data'          => $soal['tabel_data'],
            'nilai_maksimum'      => $soal['nilai_maksimum'],
            'gambar_soal'         => $this->mediaUrl($soal['gambar_soal_file'] ?? null, $token),
            'opsi'                => collect($soal['opsi'])->map(fn(array $opsi) => [
                'teks'   => $opsi['teks'],
                'gambar' => $this->mediaUrl($opsi['gambar_file'] ?? null, $token),
            ])->all(),
            'tipe_opsi'           => $soal['tipe_opsi'],
            'items'               => collect($soal['items'])->map(fn(array $item) => [
                'id' => $item['id'],
                'konten' => $item['konten'],
                'gambar' => $this->mediaUrl($item['gambar_file'] ?? null, $token),
                'urutan' => $item['urutan'],
            ])->values()->all(),
        ]);

        $jawabans = $peserta->jawabans()
            ->get(['soal_id', 'jawaban', 'jawaban_data'])
            ->mapWithKeys(fn($jawaban) => [
                (string) $jawaban->soal_id => $jawaban->jawaban_data ?? $jawaban->jawaban,
            ]);

        return response()->json([
            'soals'    => $soals,
            'jawabans' => $jawabans,
        ]);
    }

    private function buildCachedSoalPayload($mapelIds): array
    {
        return Soal::whereIn('mata_pelajaran_id', $mapelIds)
            ->with(['mataPelajaran:id,nama,kode,tipe', 'items'])
            ->orderBy('mata_pelajaran_id')
            ->orderBy('nomor_urut')
            ->get()
            ->map(fn(Soal $soal) => [
                'id'                  => $soal->id,
                'nomor_urut'          => $soal->nomor_urut,
                'mata_pelajaran'      => $soal->mataPelajaran->nama,
                'mata_pelajaran_kode' => $soal->mataPelajaran->kode,
                'teks_soal'           => $this->content->rich($soal->teks_soal),
                'tipe_soal'           => $soal->tipe_soal,
                'option_label_a'      => $soal->option_label_a ?: 'Benar',
                'option_label_b'      => $soal->option_label_b ?: 'Salah',
                'tabel_data'          => $soal->tabel_data,
                'nilai_maksimum'      => $soal->nilai_maksimum,
                'gambar_soal_file'    => $soal->gambar_soal,
                'opsi'                => $this->opsiUntukCache($soal),
                'tipe_opsi'           => $soal->tipe_opsi,
                'items'               => $soal->items->map(fn($item) => [
                    'id' => $item->id,
                    'konten' => $item->konten,
                    'gambar_file' => $item->gambar,
                    'urutan' => $item->urutan,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    private function opsiUntukCache(Soal $soal): array
    {
        $result = [];

        foreach (['A', 'B', 'C', 'D', 'E'] as $huruf) {
            $suffix = strtolower($huruf);
            $teks = $soal->{'opsi_' . $suffix};
            $gambar = $soal->{'gambar_opsi_' . $suffix};

            if (! $teks && ! $gambar) continue;

            $result[$huruf] = [
                'teks' => $teks,
                'gambar_file' => $gambar,
            ];
        }

        return $result;
    }

    private function mediaUrl(?string $filename, ?string $token): ?string
    {
        return $filename
            ? route('media.soal', ['filename' => $filename, 'token' => $token])
            : null;
    }
}
