<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataPelajaran extends Model
{
    protected $connection = 'peserta_db';

    protected $fillable = ['nama', 'kode', 'tipe', 'jumlah_soal'];

    // ── Relationships ──────────────────────────────────────────────

    public function soals(): HasMany
    {
        return $this->hasMany(Soal::class);
    }

    public function pesertas(): BelongsToMany
    {
        return $this->belongsToMany(Peserta::class, 'peserta_mata_pelajaran');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeWajib($query)
    {
        return $query->where('tipe', 'wajib');
    }

    public function scopePilihan($query)
    {
        return $query->where('tipe', 'pilihan');
    }
}
