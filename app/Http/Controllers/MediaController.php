<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    /**
     * Serve gambar soal. Route ini di-protect middleware auth:sanctum
     * sehingga hanya peserta yang sedang login yang bisa akses.
     *
     * Nama file berupa UUID (bukan sequential) sehingga tidak mudah ditebak.
     */
    public function soal(Request $request, string $filename): StreamedResponse
    {
        // Validasi: hanya karakter aman (UUID format)
        if (! preg_match('/^[a-f0-9\-]+\.(jpg|jpeg|png|webp|gif)$/i', $filename)) {
            abort(400, 'Nama file tidak valid.');
        }

        $path = 'soal-images/' . $filename;

        if (! Storage::disk('soal_images')->exists($path)) {
            abort(404, 'Gambar tidak ditemukan.');
        }

        return Storage::disk('soal_images')->response($path, null, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
