<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Peserta extends Authenticatable
{
    protected $fillable = [
        'nama',
        'nama_sekolah',
        'email',
        'no_ujian',
        'token_login',
        'token_used_at',
        'active_session_token',
        'active_exam_session_id',
        'status',
        'durasi_menit',
        'mulai_ujian_at',
        'selesai_ujian_at',
    ];

    protected $hidden = [
        'token_login',
        'active_session_token',
    ];

    protected $casts = [
        'mulai_ujian_at'   => 'datetime',
        'selesai_ujian_at' => 'datetime',
        'token_used_at'     => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────

    /** Hanya menyimpan 2 mapel PILIHAN. Mapel wajib tidak ada di pivot. */
    public function mataPelajarans(): BelongsToMany
    {
        return $this->belongsToMany(MataPelajaran::class, 'peserta_mata_pelajaran');
    }

    public function activeExamSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'active_exam_session_id');
    }

    public function jawabans(): HasMany
    {
        return $this->hasMany(Jawaban::class);
    }

    public function logPelanggarans(): HasMany
    {
        return $this->hasMany(LogPelanggaran::class);
    }

    public function nilaiDetails(): HasMany
    {
        return $this->hasMany(NilaiDetail::class);
    }

    // ── Helpers ───────────────────────────────────────────────────

    /** Sisa waktu dalam detik. Return 0 jika sudah habis/belum mulai. */
    public function sisaDetik(): int
    {
        if ($this->activeExamSession) {
            return $this->activeExamSession->remainingSeconds();
        }

        if (! $this->mulai_ujian_at) return $this->durasi_menit * 60;

        $terpakai = $this->mulai_ujian_at->diffInSeconds(now());
        return max(0, ($this->durasi_menit * 60) - $terpakai);
    }

    /** Semua ID mapel yang harus dikerjakan peserta ini (wajib + 2 pilihan). */
    public function semuaMapelIds(): \Illuminate\Support\Collection
    {
        $pilihanIds = $this->mataPelajarans()->pluck('mata_pelajarans.id');
        $wajibIds   = MataPelajaran::wajib()->pluck('id');

        return $pilihanIds->merge($wajibIds)->unique()->values();
    }
}
