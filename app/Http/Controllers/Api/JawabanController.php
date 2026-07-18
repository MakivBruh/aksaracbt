<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JawabanController extends Controller
{
    /**
     * Simpan atau update satu jawaban (dipanggil otomatis tiap peserta klik opsi).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'soal_id' => 'required|integer',
            'jawaban' => 'nullable|in:A,B,C,D,E',
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
        $soalValid = \App\Models\Soal::where('id', $request->soal_id)
            ->whereIn('mata_pelajaran_id', $mapelIds)
            ->exists();

        if (! $soalValid) {
            return response()->json(['message' => 'Soal tidak valid.'], 422);
        }

        DB::table('jawabans')->upsert(
            [[
                'peserta_id' => $peserta->id,
                'soal_id'    => $request->soal_id,
                'jawaban'    => $request->jawaban, // null = hapus pilihan
                'dijawab_at' => now(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]],
            ['peserta_id', 'soal_id'],
            ['jawaban', 'dijawab_at', 'updated_at']
        );

        return response()->json(['message' => 'Jawaban tersimpan.']);
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
