<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogPelanggaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogPelanggaranController extends Controller
{
    /**
     * Catat satu kejadian pelanggaran.
     * Frontend memanggil ini setiap kali peserta keluar mode ujian
     * atau memakai interaksi yang tidak diperbolehkan.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'tipe'     => 'required|string|max:64',
            'pesan'    => 'nullable|string|max:255',
            'metadata' => 'nullable',
        ]);

        $peserta = $request->user();
        $incomingMetadata = $request->metadata ?? [];

        if (is_string($incomingMetadata)) {
            $decoded = json_decode($incomingMetadata, true);
            $incomingMetadata = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($incomingMetadata)) {
            $incomingMetadata = [];
        }

        $metadata = array_merge($incomingMetadata, [
            'pesan' => $request->pesan,
            'url' => $request->headers->get('referer'),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
        ]);

        LogPelanggaran::create([
            'peserta_id'  => $peserta->id,
            'tipe'        => $request->tipe,
            'terjadi_at'  => now(),
            'metadata'    => $metadata,
        ]);

        $total = LogPelanggaran::where('peserta_id', $peserta->id)->count();

        if ($total >= 3) {
            $peserta->update([
                'active_session_token' => null,
            ]);
        }

        return response()->json([
            'message'           => 'Pelanggaran dicatat.',
            'total_pelanggaran' => $total,
            'locked'            => $total >= 3,
        ]);
    }
}
