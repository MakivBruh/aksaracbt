<?php

namespace App\Imports;

use App\Models\MataPelajaran;
use App\Models\Peserta;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use ZipArchive;

class PesertaImport
{
    private array $warnings  = [];
    private int   $imported  = 0;
    private int   $skipped   = 0;

    /**
     * Kolom yang diharapkan di Excel:
     *   nama | nama_sekolah | email | mapel_pilihan_1 | mapel_pilihan_2
     *
     * Nama mapel harus sesuai dengan kolom `nama` di tabel mata_pelajarans
     * (case-insensitive, trim spasi otomatis).
     */
    public function importFromCsv(string $path): void
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            $this->warnings[] = 'File CSV tidak bisa dibaca.';
            return;
        }

        $firstLine = fgets($handle);

        if ($firstLine === false) {
            fclose($handle);
            $this->warnings[] = 'File CSV kosong atau formatnya tidak valid.';
            return;
        }

        $delimiter = $this->detectDelimiter($firstLine);
        rewind($handle);

        $headings = fgetcsv($handle, 0, $delimiter);

        if (! is_array($headings)) {
            fclose($handle);
            $this->warnings[] = 'File CSV kosong atau formatnya tidak valid.';
            return;
        }

        $headings = array_map(fn($h) => $this->normalizeHeading((string) $h), $headings);
        $rows = [];

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($data === [null] || $data === false) {
                continue;
            }

            $rows[] = array_combine($headings, array_slice(array_pad($data, count($headings), null), 0, count($headings)));
        }

        fclose($handle);
        $this->collection($rows);
    }

    public function importFromXlsx(string $path): void
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            $this->warnings[] = 'File XLSX tidak bisa dibaca.';
            return;
        }

        $sharedStrings = $this->readSharedStrings($zip);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            $this->warnings[] = 'Sheet pertama tidak ditemukan di file XLSX.';
            return;
        }

        $sheet = new SimpleXMLElement($sheetXml);
        $rows = [];
        $headings = [];

        foreach ($sheet->sheetData->row as $rowIndex => $row) {
            $values = [];

            foreach ($row->c as $cell) {
                $cellRef = (string) $cell['r'];
                $columnIndex = $this->columnIndexFromCellRef($cellRef);
                $values[$columnIndex] = $this->cellValue($cell, $sharedStrings);
            }

            if ($rowIndex === 0) {
                ksort($values);
                $headings = array_map(fn($h) => $this->normalizeHeading((string) $h), array_values($values));
                continue;
            }

            if (! $headings) {
                $this->warnings[] = 'Header XLSX tidak ditemukan.';
                return;
            }

            $normalized = [];
            for ($i = 0; $i < count($headings); $i++) {
                $normalized[$headings[$i]] = $values[$i] ?? null;
            }

            if (collect($normalized)->filter(fn($value) => trim((string) $value) !== '')->isNotEmpty()) {
                $rows[] = $normalized;
            }
        }

        $this->collection($rows);
    }

    private function collection(array $rows): void
    {
        // Cache semua mapel pilihan agar tidak query per baris
        $semuaMapelPilihan = MataPelajaran::pilihan()
            ->get()
            ->flatMap(function ($mapel) {
                $keys = [$this->normalizeMapelName($mapel->nama)];

                if ($mapel->kode === 'MAL') {
                    $keys[] = $this->normalizeMapelName('Matematika Lanjutan');
                    $keys[] = $this->normalizeMapelName('Matematika Tingkat Lanjut');
                }

                return collect($keys)->mapWithKeys(fn($key) => [$key => $mapel]);
            });

        foreach ($rows as $index => $row) {
            $baris = $index + 2; // +2 karena baris 1 = heading
            $nama  = trim((string) $this->value($row, ['nama', 'name', 'nama_lengkap']));
            $namaSekolah = trim((string) $this->value($row, [
                'nama_sekolah',
                'sekolah',
                'asal_sekolah',
                'nama_asal_sekolah',
                'instansi',
                'school',
                'school_name',
            ]));
            $email = strtolower(trim((string) $this->value($row, ['email', 'e_mail', 'email_peserta', 'alamat_email', 'email_address'])));

            if (! $nama || ! $email) {
                $this->warnings[] = "Baris {$baris}: kolom nama/email kosong, dilewati.";
                $this->skipped++;
                continue;
            }

            // Buat atau update peserta berdasarkan email (idempotent)
            $peserta = Peserta::updateOrCreate(
                ['email' => $email],
                [
                    'nama'        => $nama,
                    'nama_sekolah' => $namaSekolah !== '' ? $namaSekolah : null,
                    'no_ujian'    => $this->generateNoUjian($email),
                    'active_session_token' => null,
                ]
            );

            // Resolve mapel pilihan
            $pilihanIds = [];
            foreach ([
                ['mapel_pilihan_1', 'mapel_1', 'pilihan_1', 'mapel_pilihan1'],
                ['mapel_pilihan_2', 'mapel_2', 'pilihan_2', 'mapel_pilihan2'],
            ] as $aliases) {
                $namaMapel = $this->normalizeMapelName((string) $this->value($row, $aliases));

                if (! $namaMapel) continue;

                $mapel = $semuaMapelPilihan->get($namaMapel);

                if ($mapel) {
                    $pilihanIds[] = $mapel->id;
                } else {
                    $this->warnings[] = "Baris {$baris} ({$nama}): mapel '{$namaMapel}' tidak ditemukan.";
                    Log::warning("PesertaImport: mapel tidak ditemukan", [
                        'baris' => $baris,
                        'nama'  => $nama,
                        'mapel' => $namaMapel,
                    ]);
                }
            }

            if (! empty($pilihanIds)) {
                // sync tanpa detach agar import ulang tidak hilangkan pilihan lama
                $peserta->mataPelajarans()->syncWithoutDetaching($pilihanIds);
            }

            $this->imported++;
        }
    }

    // ── Getters (untuk controller) ─────────────────────────────────

    public function getWarnings(): array { return $this->warnings; }
    public function getImported(): int   { return $this->imported; }
    public function getSkipped(): int    { return $this->skipped; }

    // ── Private ────────────────────────────────────────────────────

    private function detectDelimiter(string $line): string
    {
        $delimiters = [',' => 0, ';' => 0, "\t" => 0];

        foreach ($delimiters as $delimiter => $_) {
            $delimiters[$delimiter] = substr_count($line, $delimiter);
        }

        arsort($delimiters);

        return array_key_first($delimiters) ?: ',';
    }

    private function normalizeHeading(string $heading): string
    {
        $heading = preg_replace('/^\xEF\xBB\xBF/', '', $heading) ?? $heading;
        $heading = strtolower(trim($heading));
        $heading = preg_replace('/[^a-z0-9]+/', '_', $heading) ?? $heading;

        return trim($heading, '_');
    }

    private function value(array $row, array $aliases): mixed
    {
        foreach ($aliases as $alias) {
            if (array_key_exists($alias, $row)) {
                return $row[$alias];
            }
        }

        return null;
    }

    private function normalizeMapelName(string $name): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim($name)) ?? trim($name));
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) return [];

        $shared = new SimpleXMLElement($xml);
        $values = [];

        foreach ($shared->si as $item) {
            if (isset($item->t)) {
                $values[] = (string) $item->t;
                continue;
            }

            $text = '';
            foreach ($item->r as $run) {
                $text .= (string) $run->t;
            }
            $values[] = $text;
        }

        return $values;
    }

    private function cellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];
        $value = (string) ($cell->v ?? '');

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        if ($type === 'inlineStr') {
            return (string) ($cell->is->t ?? '');
        }

        return $value;
    }

    private function columnIndexFromCellRef(string $cellRef): int
    {
        preg_match('/^[A-Z]+/', strtoupper($cellRef), $matches);
        $letters = $matches[0] ?? 'A';
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function generateNoUjian(string $email): string
    {
        // Format: TKA-XXXXXX (6 karakter hex dari hash email)
        return 'TKA-' . strtoupper(substr(md5($email), 0, 6));
    }
}
