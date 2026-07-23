<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Soal;

class JawabanController extends Controller
{
    /**
     * Simpan atau update satu jawaban (dipanggil otomatis tiap peserta klik opsi).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'soal_id' => 'required|integer',
            'jawaban' => 'nullable',
        ]);

        $peserta = $request->user();

        if ($peserta->status === 'selesai') {
            return response()->json(['message' => 'Ujian sudah selesai.'], 403);
        }

        if ($peserta->activeExamSession?->hasEnded()) {
            return response()->json(['message' => 'Waktu ujian sudah habis.'], 403);
        }

        // Pastikan soal ini memang untuk peserta yang bersangkutan
        $mapelIds = $peserta->semuaMapelIds();
        $soal = Soal::with('items')->where('id', $request->soal_id)
            ->whereIn('mata_pelajaran_id', $mapelIds)
            ->first();

        if (! $soal) {
            return response()->json(['message' => 'Soal tidak valid.'], 422);
        }

        $jawaban = $this->normalisasiJawaban($soal, $request->input('jawaban'));

        DB::table('jawabans')->upsert(
            [[
                'peserta_id' => $peserta->id,
                'soal_id'    => $request->soal_id,
                'jawaban'    => $soal->tipe_soal === 'pilihan_ganda' ? $jawaban : null,
                'jawaban_data' => $soal->tipe_soal === 'pilihan_ganda' ? null : json_encode($jawaban),
                'dijawab_at' => now(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]],
            ['peserta_id', 'soal_id'],
            ['jawaban', 'jawaban_data', 'dijawab_at', 'updated_at']
        );

        return response()->json(['message' => 'Jawaban tersimpan.']);
    }

    private function normalisasiJawaban(Soal $soal, mixed $jawaban): string|array|null
    {
        if ($soal->tipe_soal === 'pilihan_ganda') {
            $value = strtoupper((string) $jawaban);
            abort_unless(in_array($value, ['A', 'B', 'C', 'D', 'E'], true), 422, 'Jawaban tidak valid.');
            return $value;
        }

        $itemIds = $soal->items->pluck('id')->map(fn($id) => (string) $id);

        if ($soal->tipe_soal === 'benar_salah') {
            abort_unless(is_array($jawaban), 422, 'Jawaban tidak valid.');
            return collect($jawaban)
                ->filter(fn($value, $id) => $itemIds->contains((string) $id) && in_array(strtoupper((string) $value), ['A', 'B'], true))
                ->mapWithKeys(fn($value, $id) => [(string) $id => strtoupper((string) $value)])
                ->all();
        }

        abort_unless(is_array($jawaban), 422, 'Jawaban tidak valid.');
        return collect($jawaban)
            ->map(fn($id) => (string) $id)
            ->filter(fn($id) => $itemIds->contains($id))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Peserta menyatakan selesai ujian (atau timer habis).
     * Token langsung dicabut setelah ini.
     */
    public function selesai(Request $request): JsonResponse
    {
        $peserta = $request->user();

        if ($peserta->status === 'selesai') {
            return response()->json(['message' => 'Ujian sudah selesai sebelumnya.'], 409);
        }

        $peserta->update([
            'status'           => 'selesai',
            'selesai_ujian_at' => now(),
            'active_session_token' => null,
        ]);

        // Hapus token agar tidak bisa kembali ke halaman ujian
        return response()->json(['message' => 'Ujian selesai. Terima kasih sudah mengerjakan!']);
    }
}
