<?php

use App\Http\Controllers\Admin\ExamSessionController;
use App\Http\Controllers\Admin\PesertaImportController;
use App\Http\Controllers\HitungSkorController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\SoalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ── Halaman ujian ───────────────────────────────────────────────────
Route::get('/', fn() => view('auth.login'))->name('peserta.login');
Route::get('/ujian', fn() => view('exam.index'))->name('exam.index');
Route::get('/selesai', fn() => view('exam.selesai'))->name('exam.selesai');

Route::get('/admin/login', fn() => view('auth.admin-login'))->name('login');

Route::post('/admin/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        return redirect()->intended('/admin/peserta');
    }

    return back()->withErrors(['email' => 'Email atau password tidak sesuai.'])->onlyInput('email');
})->name('login.store');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/admin/login');
})->middleware('auth')->name('logout');

// ── Gambar soal terproteksi ─────────────────────────────────────────
// Middleware auth:sanctum memastikan hanya peserta login yang bisa akses.
// Token dikirim via Authorization header (bukan cookie) sehingga tidak
// bisa diakses langsung dengan klik URL di browser biasa.
Route::get('/media/soal/{filename}', [MediaController::class, 'soal'])
    ->middleware('auth.peserta')
    ->name('media.soal');

// ── Admin: kelola akun peserta ───────────────────────────────────────
// TODO: pasang Laravel Breeze/Fortify untuk login panitia, lalu ganti
// middleware 'auth' di bawah ini jadi guard panitia (bukan guard peserta).
// JANGAN deploy tanpa proteksi auth — ini nyimpen token_login peserta.
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn() => redirect()->route('admin.peserta.index'));
    Route::get('media/soal/{filename}', [MediaController::class, 'soalAdmin'])->name('media.soal');

    Route::get('sessions/display', [ExamSessionController::class, 'display'])->name('sessions.display');
    Route::resource('sessions', ExamSessionController::class)
        ->except(['show', 'destroy'])
        ->parameters(['sessions' => 'session']);

    Route::get('peserta',                    [PesertaImportController::class, 'index'])->name('peserta.index');
    Route::get('peserta/tambah',             [PesertaImportController::class, 'create'])->name('peserta.create');
    Route::post('peserta/tambah',            [PesertaImportController::class, 'store'])->name('peserta.store');
    Route::get('peserta/import',              [PesertaImportController::class, 'importForm'])->name('peserta.import');
    Route::post('peserta/import',             [PesertaImportController::class, 'importStore'])->name('peserta.import.store');
    Route::post('peserta/{peserta}/reset-token', [PesertaImportController::class, 'resetToken'])->name('peserta.reset-token');
    Route::post('peserta/{peserta}/unlock', [PesertaImportController::class, 'unlock'])->name('peserta.unlock');
    Route::post('peserta/selesaikan-semua', [PesertaImportController::class, 'finishAll'])->name('peserta.finish-all');
    Route::patch('peserta/{peserta}/status', [PesertaImportController::class, 'updateStatus'])->name('peserta.status');
    Route::delete('peserta/{peserta}', [PesertaImportController::class, 'destroy'])->name('peserta.destroy');

    Route::post('soal/sync', [SoalController::class, 'sync'])->name('soal.sync');
    Route::resource('soal', SoalController::class)->except(['show'])->names([
        'index' => 'soal.index',
        'create' => 'soal.create',
        'store' => 'soal.store',
        'edit' => 'soal.edit',
        'update' => 'soal.update',
        'destroy' => 'soal.destroy',
    ]);

    Route::get('skor', [HitungSkorController::class, 'index'])->name('skor.index');
    Route::get('podium', [HitungSkorController::class, 'podium'])->name('podium.index');
    Route::get('skor/{peserta}/statistik', [HitungSkorController::class, 'show'])->name('skor.show');
    Route::get('skor/{id}/peserta', [HitungSkorController::class, 'peserta'])->name('skor.peserta');
});
