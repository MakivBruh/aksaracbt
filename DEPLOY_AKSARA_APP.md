# Deploy Aksara CBT di VPS

Project gabungan ada di folder `aksara-app`.

## Target

- PHP 8.4
- MariaDB
- Nginx/Apache root ke folder `public`
- Satu Laravel project untuk peserta dan admin
- Data soal peserta tetap terpisah dari kunci jawaban admin

## Database Yang Disarankan

Buat 2 database di MariaDB:

```sql
CREATE DATABASE aksara_peserta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE aksara_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

`aksara_peserta` menyimpan peserta, jawaban, sesi, nilai, dan soal tanpa kunci.
`aksara_admin` menyimpan soal admin dengan `kunci_jawaban`.

## Contoh .env Production

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-kamu.com

DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aksara_peserta
DB_USERNAME=aksara_peserta
DB_PASSWORD=password_peserta

ADMIN_DB_CONNECTION=mariadb
ADMIN_DB_HOST=127.0.0.1
ADMIN_DB_PORT=3306
ADMIN_DB_DATABASE=aksara_admin
ADMIN_DB_USERNAME=aksara_admin
ADMIN_DB_PASSWORD=password_admin

PESERTA_DB_CONNECTION=mariadb
PESERTA_DB_HOST=127.0.0.1
PESERTA_DB_PORT=3306
PESERTA_DB_DATABASE=aksara_peserta
PESERTA_DB_USERNAME=aksara_peserta
PESERTA_DB_PASSWORD=password_peserta

PESERTA_SCORING_DB_CONNECTION=mariadb
PESERTA_SCORING_DB_HOST=127.0.0.1
PESERTA_SCORING_DB_PORT=3306
PESERTA_SCORING_DB_DATABASE=aksara_peserta
PESERTA_SCORING_DB_USERNAME=aksara_peserta
PESERTA_SCORING_DB_PASSWORD=password_peserta

PESERTA_SOAL_SYNC_DB_CONNECTION=mariadb
PESERTA_SOAL_SYNC_DB_HOST=127.0.0.1
PESERTA_SOAL_SYNC_DB_PORT=3306
PESERTA_SOAL_SYNC_DB_DATABASE=aksara_peserta
PESERTA_SOAL_SYNC_DB_USERNAME=aksara_peserta
PESERTA_SOAL_SYNC_DB_PASSWORD=password_peserta
```

Untuk keamanan lebih ketat, user `PESERTA_SCORING_DB_*` dan `PESERTA_SOAL_SYNC_DB_*`
bisa dibuat terpisah dengan grant terbatas.

## Perintah Deploy

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Route Utama

- `/` halaman login peserta
- `/ujian` halaman ujian peserta
- `/admin/login` login admin
- `/admin/peserta` kelola peserta
- `/admin/soal` bank soal
- `/admin/skor` rekap skor
- `/admin/podium` podium/showcase
