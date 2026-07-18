<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Soal extends Model
{
    protected $fillable = [
        'mata_pelajaran_id',
        'nomor_urut',
        'teks_soal',
        'gambar_soal',
        'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e',
        'gambar_opsi_a', 'gambar_opsi_b', 'gambar_opsi_c', 'gambar_opsi_d', 'gambar_opsi_e',
        'tipe_opsi',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * Kembalikan array opsi yang siap di-render.
     * URL gambar dikonversi ke route terproteksi.
     */
    public function opsiUntukApi(?string $token = null): array
    {
        $opsiHuruf = ['A', 'B', 'C', 'D', 'E'];
        $result    = [];

        foreach ($opsiHuruf as $h) {
            $teks   = $this->{'opsi_' . strtolower($h)};
            $gambar = $this->{'gambar_opsi_' . strtolower($h)};

            if (! $teks && ! $gambar) continue; // Skip opsi kosong

            $result[$h] = [
                'teks'   => $teks,
                'gambar' => $gambar ? route('media.soal', ['filename' => $gambar, 'token' => $token]) : null,
            ];
        }

        return $result;
    }
}
