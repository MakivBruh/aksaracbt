<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\Peserta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Login peserta menggunakan token_login dari panitia.
     * Satu token = satu sesi aktif (token lama otomatis dicabut).
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token_login' => 'required|string|max:64',
        ]);

        $peserta = Peserta::where('email', strtolower($request->email))->first();

        if (! $peserta) {
            return response()->json(['message' => 'Email tidak terdaftar.'], 401);
        }

        $examSession = ExamSession::where('token', strtoupper($request->token_login))
            ->where('is_active', true)
            ->first();

        if (! $examSession) {
            return response()->json(['message' => 'Token tryout tidak valid atau tidak aktif.'], 401);
        }

        if (! $examSession->hasStarted()) {
            return response()->json([
                'message' => 'Tryout belum dimulai. Token aktif pada '.$examSession->starts_at->format('d M Y H:i').' WIB.',
            ], 403);
        }

        if ($examSession->hasEnded()) {
            return response()->json(['message' => 'Waktu tryout untuk token ini sudah selesai.'], 403);
        }

        if ($peserta->status === 'selesai') {
            return response()->json(['message' => 'Ujian Anda sudah selesai.'], 403);
        }

        // Cabut semua token lama → single session
        // Mulai ujian jika baru pertama kali login
        $sessionToken = Str::random(64);

        if ($peserta->status === 'belum_mulai') {
            $peserta->status = 'sedang_ujian';
            $peserta->mulai_ujian_at = $examSession->starts_at;
        }

        $peserta->active_session_token = $sessionToken;
        $peserta->active_exam_session_id = $examSession->id;
        $peserta->durasi_menit = $examSession->duration_minutes;
        $peserta->save();

        return response()->json([
            'token'   => $sessionToken,
            'peserta' => $this->formatPeserta($peserta->fresh()),
        ]);
    }

    /**
     * Informasi peserta yang sedang login (dipakai saat reload halaman).
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($this->formatPeserta($request->user()));
    }

    // ── Private ────────────────────────────────────────────────────

    private function formatPeserta(Peserta $peserta): array
    {
        return [
            'id'             => $peserta->id,
            'nama'           => $peserta->nama,
            'nama_sekolah'   => $peserta->nama_sekolah,
            'email'          => $peserta->email,
            'no_ujian'       => $peserta->no_ujian,
            'status'         => $peserta->status,
            'durasi_menit'   => $peserta->activeExamSession?->duration_minutes ?? $peserta->durasi_menit,
            'mulai_ujian_at' => $peserta->mulai_ujian_at?->toISOString(),
            'sisa_detik'     => $peserta->sisaDetik(),
        ];
    }
}
