<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends Model
{
    protected $fillable = [
        'name',
        'token',
        'starts_at',
        'duration_minutes',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function pesertas(): HasMany
    {
        return $this->hasMany(Peserta::class, 'active_exam_session_id');
    }

    public function endsAt()
    {
        return $this->starts_at->copy()->addMinutes($this->duration_minutes);
    }

    public function remainingSeconds(): int
    {
        return max(0, now()->diffInSeconds($this->endsAt(), false));
    }

    public function hasStarted(): bool
    {
        return now()->greaterThanOrEqualTo($this->starts_at);
    }

    public function hasEnded(): bool
    {
        return now()->greaterThanOrEqualTo($this->endsAt());
    }
}
