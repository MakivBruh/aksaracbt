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
        return $this->response($filename);
    }

    public function soalAdmin(string $filename): StreamedResponse
    {
        return $this->response($filename);
    }

    private function response(string $filename): StreamedResponse
    {
        if (! preg_match('/^[a-f0-9\-]+\.(jpg|jpeg|png|webp|gif)$/i', $filename)) {
            abort(400, 'Nama file tidak valid.');
        }

        $path = 'soal-images/' . $filename;
        $disk = Storage::disk('soal_images');

        if (! $disk->exists($path)) {
            $legacy = Storage::disk('soal_images_legacy');
            if (! $legacy->exists($path)) {
                abort(404, 'Gambar tidak ditemukan.');
            }
            $disk = $legacy;
        }

        return $disk->response($path, null, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
