<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->rebuildSqliteTable();
            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE log_pelanggarans MODIFY tipe VARCHAR(64) NOT NULL');
        }
    }

    public function down(): void
    {
        // Tidak dikembalikan ke enum lama agar data pelanggaran baru tidak hilang.
    }

    private function rebuildSqliteTable(): void
    {
        if (! Schema::hasTable('log_pelanggarans')) return;

        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement(<<<'SQL'
CREATE TABLE log_pelanggarans_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    peserta_id INTEGER NOT NULL,
    tipe VARCHAR NOT NULL,
    terjadi_at DATETIME NOT NULL,
    metadata TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (peserta_id) REFERENCES pesertas (id) ON DELETE CASCADE
)
SQL);

        DB::statement(<<<'SQL'
INSERT INTO log_pelanggarans_new (id, peserta_id, tipe, terjadi_at, metadata, created_at, updated_at)
SELECT id, peserta_id, tipe, terjadi_at, metadata, created_at, updated_at
FROM log_pelanggarans
SQL);

        DB::statement('DROP TABLE log_pelanggarans');
        DB::statement('ALTER TABLE log_pelanggarans_new RENAME TO log_pelanggarans');
        DB::statement('CREATE INDEX log_pelanggarans_peserta_id_index ON log_pelanggarans (peserta_id)');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
