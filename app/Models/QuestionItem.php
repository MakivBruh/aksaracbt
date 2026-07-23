<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionItem extends Model
{
    protected $fillable = ['soal_id', 'konten', 'gambar', 'urutan'];

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }
}
