<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],
        'soal_images' => [
            'driver' => 'local',
            'root' => env('SOAL_IMAGES_ROOT', storage_path('app/private')),
            'visibility' => 'private',
            'throw' => false,
        ],
        'soal_images_legacy' => [
            'driver' => 'local',
            'root' => env('SOAL_IMAGES_LEGACY_ROOT', dirname(base_path()).DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'soal-images'),
            'visibility' => 'private',
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];

// ─────────────────────────────────────────────────────────────────────
// TAMBAHKAN disk 'soal_images' ini ke config/filesystems.php,
// di array 'disks', DI KEDUA APP (admin-app DAN peserta-app).
// Konfigurasinya harus SAMA PERSIS di keduanya (bucket sama) supaya
// gambar yang di-upload panitia lewat admin-app langsung bisa
// dibaca peserta-app tanpa perlu proses copy file manual.
// ─────────────────────────────────────────────────────────────────────
//
// 'disks' => [
//
//     // ... disk default lainnya ...
//
//     'soal_images' => [
//         'driver'                  => 's3',
//         'key'                     => env('SOAL_IMAGES_ACCESS_KEY_ID'),
//         'secret'                  => env('SOAL_IMAGES_SECRET_ACCESS_KEY'),
//         'region'                  => env('SOAL_IMAGES_DEFAULT_REGION', 'auto'),
//         'bucket'                  => env('SOAL_IMAGES_BUCKET'),
//         'endpoint'                => env('SOAL_IMAGES_ENDPOINT'), // URL R2/S3-compatible
//         'use_path_style_endpoint' => true,
//         'visibility'              => 'private', // wajib private, diserve lewat MediaController
//     ],
// ],
//
// ─────────────────────────────────────────────────────────────────────
// .env — SAMA di admin-app dan peserta-app:
//
// SOAL_IMAGES_ACCESS_KEY_ID=xxxxxxxx
// SOAL_IMAGES_SECRET_ACCESS_KEY=xxxxxxxx
// SOAL_IMAGES_BUCKET=tka-soal-images
// SOAL_IMAGES_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
// SOAL_IMAGES_DEFAULT_REGION=auto
//
// composer require league/flysystem-aws-s3-v3 (dibutuhkan driver s3)
//
// ─────────────────────────────────────────────────────────────────────
// ALTERNATIF LEBIH SIMPEL (kalau 2 app di-deploy di 1 server yang sama):
//
// Bikin 1 folder fisik di luar kedua project, misal /var/tka-soal-images,
// lalu symlink storage/app/soal-images di KEDUA app ke folder itu:
//
//   ln -s /var/tka-soal-images admin-app/storage/app/soal-images
//   ln -s /var/tka-soal-images peserta-app/storage/app/soal-images
//
// Terus disk 'soal_images' di filesystems.php cukup pakai driver 'local'
// biasa yang root-nya storage_path('app/soal-images'). Gak perlu S3 sama
// sekali, tapi WAJIB 1 server yang sama.
// ─────────────────────────────────────────────────────────────────────

return []; // File ini hanya komentar panduan, bukan file yang perlu dipakai langsung.
