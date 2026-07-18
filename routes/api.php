<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JawabanController;
use App\Http\Controllers\Api\LogPelanggaranController;
use App\Http\Controllers\Api\SoalController;
use Illuminate\Support\Facades\Route;

// ── Public ─────────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:peserta-login');

// ── Protected (Sanctum token) ───────────────────────────────────────
Route::middleware(['auth.peserta', 'throttle:peserta-api'])->group(function () {
    Route::get('/me',      [AuthController::class, 'me']);
    Route::get('/soal',    [SoalController::class, 'index']);
    Route::post('/jawaban',          [JawabanController::class, 'store']);
    Route::post('/selesai',          [JawabanController::class, 'selesai']);
    Route::post('/log-pelanggaran',  [LogPelanggaranController::class, 'store']);
});
