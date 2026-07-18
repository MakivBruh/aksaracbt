<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminSoal extends Model
{
    protected $connection = 'admin_db';

    protected $table = 'soals';

    protected $fillable = [
        'mata_pelajaran_id',
        'nomor_urut',
        'teks_soal',
        'gambar_soal',
        'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e',
        'gambar_opsi_a', 'gambar_opsi_b', 'gambar_opsi_c', 'gambar_opsi_d', 'gambar_opsi_e',
        'tipe_opsi',
        'kunci_jawaban',
    ];

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }
}
