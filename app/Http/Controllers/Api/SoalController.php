<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Soal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SoalController extends Controller
{
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
            'peserta_soal_payload:v2:' . md5($mapelIds->implode('-') . '|' . $soalVersion),
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
            'gambar_soal'         => $this->mediaUrl($soal['gambar_soal_file'] ?? null, $token),
            'opsi'                => collect($soal['opsi'])->map(fn(array $opsi) => [
                'teks'   => $opsi['teks'],
                'gambar' => $this->mediaUrl($opsi['gambar_file'] ?? null, $token),
            ])->all(),
            'tipe_opsi'           => $soal['tipe_opsi'],
        ]);

        $jawabans = $peserta->jawabans()
            ->pluck('jawaban', 'soal_id');

        return response()->json([
            'soals'    => $soals,
            'jawabans' => $jawabans,
        ]);
    }

    private function buildCachedSoalPayload($mapelIds): array
    {
        return Soal::whereIn('mata_pelajaran_id', $mapelIds)
            ->with('mataPelajaran:id,nama,kode,tipe')
            ->orderBy('mata_pelajaran_id')
            ->orderBy('nomor_urut')
            ->get()
            ->map(fn(Soal $soal) => [
                'id'                  => $soal->id,
                'nomor_urut'          => $soal->nomor_urut,
                'mata_pelajaran'      => $soal->mataPelajaran->nama,
                'mata_pelajaran_kode' => $soal->mataPelajaran->kode,
                'teks_soal'           => $soal->teks_soal,
                'gambar_soal_file'    => $soal->gambar_soal,
                'opsi'                => $this->opsiUntukCache($soal),
                'tipe_opsi'           => $soal->tipe_opsi,
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
