<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminSoal extends Model
{
    protected $connection = 'admin_db';

    protected $table = 'soals';

    protected $fillable = [
        'mata_pelajaran_id',
        'nomor_urut',
        'tipe_soal',
        'option_label_a', 'option_label_b',
        'teks_soal',
        'tabel_data',
        'gambar_soal',
        'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e',
        'gambar_opsi_a', 'gambar_opsi_b', 'gambar_opsi_c', 'gambar_opsi_d', 'gambar_opsi_e',
        'tipe_opsi',
        'kunci_jawaban',
        'nilai_maksimum',
    ];

    protected $casts = [
        'tabel_data' => 'array',
        'nilai_maksimum' => 'decimal:4',
    ];

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(AdminQuestionItem::class, 'soal_id')->orderBy('urutan');
    }
}
