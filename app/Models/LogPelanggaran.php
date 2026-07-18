<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogPelanggaran extends Model
{
    protected $fillable = ['peserta_id', 'tipe', 'terjadi_at', 'metadata'];

    protected $casts = [
        'terjadi_at' => 'datetime',
        'metadata'   => 'array',
    ];

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }
}
