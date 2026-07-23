<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminQuestionItem extends Model
{
    protected $connection = 'admin_db';
    protected $table = 'question_items';
    protected $fillable = ['soal_id', 'konten', 'gambar', 'is_correct', 'correct_value', 'urutan'];
    protected $casts = ['is_correct' => 'boolean'];

    public function soal(): BelongsTo
    {
        return $this->belongsTo(AdminSoal::class, 'soal_id');
    }
}
