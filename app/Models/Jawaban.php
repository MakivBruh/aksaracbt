<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jawaban extends Model
{
    protected $fillable = ['peserta_id', 'soal_id', 'jawaban', 'jawaban_data', 'dijawab_at'];

    protected $casts = [
        'dijawab_at' => 'datetime',
        'jawaban_data' => 'array',
    ];

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }
}
