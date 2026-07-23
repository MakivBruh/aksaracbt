<?php

namespace App\Http\Controllers;

use App\Models\MataPelajaran;
use App\Models\AdminSoal;
use App\Services\SoalSyncService;
use App\Services\QuestionContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;

class SoalController extends Controller
{
    public function __construct(private SoalSyncService $soalSync, private QuestionContent $content) {}

    public function index(Request $request)
    {
        $mapelId = $request->integer('mapel_id') ?: null;
        $q = trim((string) $request->query('q', ''));

        $mapels = MataPelajaran::orderBy('tipe')->orderBy('nama')->get();

        $jumlahSoalByMapel = AdminSoal::query()
            ->selectRaw('mata_pelajaran_id, COUNT(*) as total')
            ->groupBy('mata_pelajaran_id')
            ->pluck('total', 'mata_pelajaran_id');

        $soals = AdminSoal::with('mataPelajaran')
            ->when($mapelId, fn($query) => $query->where('mata_pelajaran_id', $mapelId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($query) use ($q) {
                    $query->where('teks_soal', 'like', "%{$q}%")
                        ->orWhere('opsi_a', 'like', "%{$q}%")
                        ->orWhere('opsi_b', 'like', "%{$q}%")
                        ->orWhere('opsi_c', 'like', "%{$q}%")
                        ->orWhere('opsi_d', 'like', "%{$q}%")
                        ->orWhere('opsi_e', 'like', "%{$q}%")
                        ->orWhere('nomor_urut', $q);
                });
            })
            ->orderBy('mata_pelajaran_id')
            ->orderBy('nomor_urut')
            ->get()
            ->groupBy('mata_pelajaran_id');

        $totalSoal = $jumlahSoalByMapel->sum();
        $selectedMapel = $mapelId ? $mapels->firstWhere('id', $mapelId) : null;

        return view('admin.soal.index', compact(
            'soals',
            'mapels',
            'jumlahSoalByMapel',
            'totalSoal',
            'selectedMapel',
            'mapelId',
            'q',
        ));
    }

    public function create(Request $request)
    {
        $mapels = MataPelajaran::orderBy('tipe')->orderBy('nama')->get();
        $selectedMapelId = $request->integer('mapel_id') ?: null;

        return view('admin.soal.create', compact('mapels', 'selectedMapelId'));
    }

    public function sync(): RedirectResponse
    {
        $total = $this->soalSync->syncSemua();

        return redirect()->route('admin.soal.index')
            ->with('success', "Sinkronisasi selesai. {$total} soal admin sekarang aktif di halaman ujian.");
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rulesValidasi());
        $this->validasiBisnis($validated, $request);

        $data = $request->only([
            'mata_pelajaran_id', 'nomor_urut', 'teks_soal', 'tipe_soal', 'option_label_a', 'option_label_b',
            'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e',
            'tipe_opsi', 'kunci_jawaban',
        ]);
        $data['teks_soal'] = $this->content->plain($data['teks_soal'] ?? null);
        $data['option_label_a'] = $data['tipe_soal'] === 'benar_salah' ? trim((string) $data['option_label_a']) : 'Benar';
        $data['option_label_b'] = $data['tipe_soal'] === 'benar_salah' ? trim((string) $data['option_label_b']) : 'Salah';
        $data['tabel_data'] = $this->content->table($request->input('tabel_tsv'));
        $data['nilai_maksimum'] = $this->nilaiMaksimum((int) $validated['mata_pelajaran_id']);
        $data['kunci_jawaban'] = $data['tipe_soal'] === 'pilihan_ganda' ? $data['kunci_jawaban'] : 'A';

        // Upload gambar soal
        $data['gambar_soal'] = $this->uploadGambar($request, 'gambar_soal');

        // Upload gambar opsi
        foreach (['a', 'b', 'c', 'd', 'e'] as $h) {
            $data['gambar_opsi_' . $h] = $this->uploadGambar($request, 'gambar_opsi_' . $h);
        }

        $soal = AdminSoal::create($data);
        $this->simpanItems($soal, $validated['items'] ?? []);
        $this->soalSync->sync($soal);

        return redirect()->route('admin.soal.index')
            ->with('success', 'Soal berhasil ditambahkan.');
    }

    public function edit(AdminSoal $soal)
    {
        $mapels = MataPelajaran::orderBy('tipe')->orderBy('nama')->get();
        return view('admin.soal.edit', compact('soal', 'mapels'));
    }

    public function update(Request $request, AdminSoal $soal): RedirectResponse
    {
        $validated = $request->validate($this->rulesValidasi());
        $this->validasiBisnis($validated, $request, $soal);

        $data = $request->only([
            'mata_pelajaran_id', 'nomor_urut', 'teks_soal', 'tipe_soal', 'option_label_a', 'option_label_b',
            'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e',
            'tipe_opsi', 'kunci_jawaban',
        ]);
        $data['teks_soal'] = $this->content->plain($data['teks_soal'] ?? null);
        $data['option_label_a'] = $data['tipe_soal'] === 'benar_salah' ? trim((string) $data['option_label_a']) : 'Benar';
        $data['option_label_b'] = $data['tipe_soal'] === 'benar_salah' ? trim((string) $data['option_label_b']) : 'Salah';
        $data['tabel_data'] = $this->content->table($request->input('tabel_tsv'));
        $data['nilai_maksimum'] = $this->nilaiMaksimum((int) $validated['mata_pelajaran_id']);
        $data['kunci_jawaban'] = $data['tipe_soal'] === 'pilihan_ganda' ? $data['kunci_jawaban'] : 'A';

        // Ganti gambar soal jika ada upload baru
        if ($request->hasFile('gambar_soal')) {
            if ($soal->gambar_soal) {
                $this->hapusGambar($soal->gambar_soal);
            }
            $data['gambar_soal'] = $this->uploadGambar($request, 'gambar_soal');
        }

        // Ganti gambar opsi jika ada upload baru (bug lama: cuma gambar_soal yang di-handle)
        foreach (['a', 'b', 'c', 'd', 'e'] as $h) {
            $field = 'gambar_opsi_' . $h;
            if ($request->hasFile($field)) {
                if ($soal->$field) {
                    $this->hapusGambar($soal->$field);
                }
                $data[$field] = $this->uploadGambar($request, $field);
            }
        }

        $soal->update($data);
        $this->simpanItems($soal, $validated['items'] ?? []);
        $this->soalSync->sync($soal);

        return redirect()->route('admin.soal.index')
            ->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroy(AdminSoal $soal): RedirectResponse
    {
        // Hapus file gambar terkait
        foreach (['gambar_soal', 'gambar_opsi_a', 'gambar_opsi_b', 'gambar_opsi_c', 'gambar_opsi_d', 'gambar_opsi_e'] as $col) {
            if ($soal->$col) {
                $this->hapusGambar($soal->$col);
            }
        }

        foreach ($soal->items as $item) {
            if ($item->gambar) $this->hapusGambar($item->gambar);
        }

        $soal->items()->delete();
        $soal->delete();
        $this->soalSync->hapus($soal->id);

        return redirect()->route('admin.soal.index')
            ->with('success', 'Soal dihapus.');
    }

    // ── Private ────────────────────────────────────────────────────

    /**
     * Rules validasi dipakai bareng oleh store() & update().
     * PENTING: exists:mata_pelajarans,id harus eksplisit nunjuk ke
     * koneksi 'peserta_db', karena data mata pelajaran cuma ada di sana
     * (model MataPelajaran pakai koneksi itu, bukan koneksi default).
     */
    private function rulesValidasi(): array
    {
        return [
            'mata_pelajaran_id' => 'required|exists:peserta_db.mata_pelajarans,id',
            'nomor_urut'        => 'required|integer|min:1',
            'tipe_soal'         => 'required|in:pilihan_ganda,benar_salah,pilih_semua',
            'teks_soal'         => 'nullable|string|max:100000',
            'option_label_a'    => 'required_if:tipe_soal,benar_salah|nullable|string|max:80|different:option_label_b',
            'option_label_b'    => 'required_if:tipe_soal,benar_salah|nullable|string|max:80|different:option_label_a',
            'tabel_tsv'         => 'nullable|string|max:30000',
            'gambar_soal'       => 'nullable|image|max:5120', // max 5MB
            'opsi_a'            => 'nullable|string',
            'opsi_b'            => 'nullable|string',
            'opsi_c'            => 'nullable|string',
            'opsi_d'            => 'nullable|string',
            'opsi_e'            => 'nullable|string',
            'gambar_opsi_a'     => 'nullable|image|max:3072',
            'gambar_opsi_b'     => 'nullable|image|max:3072',
            'gambar_opsi_c'     => 'nullable|image|max:3072',
            'gambar_opsi_d'     => 'nullable|image|max:3072',
            'gambar_opsi_e'     => 'nullable|image|max:3072',
            'tipe_opsi'         => 'required|in:teks,gambar,campuran',
            'kunci_jawaban'     => 'required_if:tipe_soal,pilihan_ganda|nullable|in:A,B,C,D,E',
            'items'             => 'required_unless:tipe_soal,pilihan_ganda|array|min:1',
            'items.*.konten'    => 'required|string|max:10000',
            'items.*.is_correct'=> 'required|boolean',
            'items.*.correct_value' => 'required_if:tipe_soal,benar_salah|nullable|in:A,B',
            'items.*.id'        => 'nullable|integer',
            'items.*.gambar'    => 'nullable|image|max:3072',
        ];
    }

    private function nilaiMaksimum(int $mapelId): string
    {
        $tipe = MataPelajaran::whereKey($mapelId)->value('tipe');

        return $tipe === 'pilihan' ? '10.0000' : '5.0000';
    }

    private function simpanItems(AdminSoal $soal, array $items): void
    {
        if ($soal->tipe_soal === 'pilihan_ganda') {
            foreach ($soal->items as $item) {
                if ($item->gambar) $this->hapusGambar($item->gambar);
            }
            $soal->items()->delete();
            return;
        }

        $soal->items()->update(['urutan' => DB::raw('urutan + 1000')]);
        $keptIds = [];
        foreach (array_values($items) as $index => $item) {
            $existing = isset($item['id']) ? $soal->items()->whereKey((int) $item['id'])->first() : null;
            $values = [
                'konten' => $this->content->plain($item['konten']),
                'correct_value' => $soal->tipe_soal === 'benar_salah' ? ($item['correct_value'] ?? ((bool) $item['is_correct'] ? 'A' : 'B')) : null,
                'is_correct' => $soal->tipe_soal === 'benar_salah' ? (($item['correct_value'] ?? 'A') === 'A') : (bool) $item['is_correct'],
                'urutan' => $index + 1,
            ];
            if (($item['gambar'] ?? null) instanceof UploadedFile) {
                if ($existing?->gambar) $this->hapusGambar($existing->gambar);
                $values['gambar'] = $this->uploadFile($item['gambar']);
            }
            if ($existing) {
                $existing->update($values);
                $keptIds[] = $existing->id;
            } else {
                $keptIds[] = $soal->items()->create($values)->id;
            }
        }
        $removedItems = $soal->items()->whereNotIn('id', $keptIds ?: [0])->get();
        foreach ($removedItems as $removedItem) {
            if ($removedItem->gambar) $this->hapusGambar($removedItem->gambar);
        }
        $soal->items()->whereNotIn('id', $keptIds ?: [0])->delete();
    }

    private function validasiBisnis(array $data, Request $request, ?AdminSoal $existing = null): void
    {
        $data['option_label_a'] = trim((string) ($data['option_label_a'] ?? ''));
        $data['option_label_b'] = trim((string) ($data['option_label_b'] ?? ''));
        if ($data['tipe_soal'] === 'benar_salah' && ($data['option_label_a'] === '' || $data['option_label_b'] === '' || mb_strtolower($data['option_label_a']) === mb_strtolower($data['option_label_b']))) {
            throw ValidationException::withMessages(['option_label_b' => 'Kedua label wajib diisi dan tidak boleh sama.']);
        }
        $mapel = MataPelajaran::findOrFail((int) $data['mata_pelajaran_id']);
        $maxNomor = $mapel->tipe === 'pilihan' ? 15 : 20;
        if ((int) $data['nomor_urut'] > $maxNomor) {
            throw ValidationException::withMessages(['nomor_urut' => "Nomor mapel {$mapel->tipe} maksimal {$maxNomor}."]);
        }

        $duplicate = AdminSoal::where('mata_pelajaran_id', $mapel->id)
            ->where('nomor_urut', $data['nomor_urut'])
            ->when($existing, fn($query) => $query->whereKeyNot($existing->id))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['nomor_urut' => 'Nomor tersebut sudah dipakai pada mata pelajaran ini.']);
        }

        $hasContent = trim((string) ($data['teks_soal'] ?? '')) !== ''
            || trim((string) $request->input('tabel_tsv')) !== ''
            || $request->hasFile('gambar_soal')
            || (bool) $existing?->gambar_soal;
        if (! $hasContent) {
            throw ValidationException::withMessages(['teks_soal' => 'Isi soal, tabel, atau gambar wajib tersedia.']);
        }

        if ($data['tipe_soal'] === 'pilihan_ganda') {
            $filledOptions = collect(['a','b','c','d','e'])->filter(fn($key) =>
                trim((string) $request->input("opsi_{$key}")) !== ''
                || $request->hasFile("gambar_opsi_{$key}")
                || (bool) ($existing?->{"gambar_opsi_{$key}"})
            );
            if ($filledOptions->count() < 2 || ! $filledOptions->contains(strtolower((string) $data['kunci_jawaban']))) {
                throw ValidationException::withMessages(['kunci_jawaban' => 'Pilihan ganda memerlukan minimal dua opsi dan kunci harus menunjuk opsi yang terisi.']);
            }
        }

        if ($data['tipe_soal'] === 'pilih_semua' && ! collect($data['items'] ?? [])->contains(fn($item) => (bool) $item['is_correct'])) {
            throw ValidationException::withMessages(['items' => 'Minimal satu pernyataan harus ditandai tepat.']);
        }
    }

    /**
     * Upload, compress, dan simpan gambar.
     * Return: nama file (UUID.ext) atau null jika tidak ada file.
     */
    private function uploadGambar(Request $request, string $field): ?string
    {
        if (! $request->hasFile($field)) return null;

        return $this->uploadFile($request->file($field));
    }

    private function uploadFile(UploadedFile $file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        Storage::disk('soal_images')->putFileAs('soal-images', $file, $filename);

        return $filename;
    }

    private function hapusGambar(string $filename): void
    {
        $path = 'soal-images/' . $filename;
        Storage::disk('soal_images')->delete($path);
        Storage::disk('soal_images_legacy')->delete($path);
    }
}
