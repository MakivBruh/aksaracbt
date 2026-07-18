<?php

namespace App\Http\Controllers;

use App\Models\MataPelajaran;
use App\Models\AdminSoal;
use App\Services\SoalSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SoalController extends Controller
{
    public function __construct(private SoalSyncService $soalSync) {}

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
        $request->validate($this->rulesValidasi());

        $data = $request->only([
            'mata_pelajaran_id', 'nomor_urut', 'teks_soal',
            'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e',
            'tipe_opsi', 'kunci_jawaban',
        ]);

        // Upload gambar soal
        $data['gambar_soal'] = $this->uploadGambar($request, 'gambar_soal');

        // Upload gambar opsi
        foreach (['a', 'b', 'c', 'd', 'e'] as $h) {
            $data['gambar_opsi_' . $h] = $this->uploadGambar($request, 'gambar_opsi_' . $h);
        }

        $soal = AdminSoal::create($data);
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
        $request->validate($this->rulesValidasi());

        $data = $request->only([
            'mata_pelajaran_id', 'nomor_urut', 'teks_soal',
            'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e',
            'tipe_opsi', 'kunci_jawaban',
        ]);

        // Ganti gambar soal jika ada upload baru
        if ($request->hasFile('gambar_soal')) {
            if ($soal->gambar_soal) {
                Storage::disk('soal_images')->delete('soal-images/' . $soal->gambar_soal);
            }
            $data['gambar_soal'] = $this->uploadGambar($request, 'gambar_soal');
        }

        // Ganti gambar opsi jika ada upload baru (bug lama: cuma gambar_soal yang di-handle)
        foreach (['a', 'b', 'c', 'd', 'e'] as $h) {
            $field = 'gambar_opsi_' . $h;
            if ($request->hasFile($field)) {
                if ($soal->$field) {
                    Storage::disk('soal_images')->delete('soal-images/' . $soal->$field);
                }
                $data[$field] = $this->uploadGambar($request, $field);
            }
        }

        $soal->update($data);
        $this->soalSync->sync($soal);

        return redirect()->route('admin.soal.index')
            ->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroy(AdminSoal $soal): RedirectResponse
    {
        // Hapus file gambar terkait
        foreach (['gambar_soal', 'gambar_opsi_a', 'gambar_opsi_b', 'gambar_opsi_c', 'gambar_opsi_d', 'gambar_opsi_e'] as $col) {
            if ($soal->$col) {
                Storage::disk('soal_images')->delete('soal-images/' . $soal->$col);
            }
        }

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
            'teks_soal'         => 'nullable|string',
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
            'kunci_jawaban'     => 'required|in:A,B,C,D,E',
        ];
    }

    /**
     * Upload, compress, dan simpan gambar.
     * Return: nama file (UUID.ext) atau null jika tidak ada file.
     */
    private function uploadGambar(Request $request, string $field): ?string
    {
        if (! $request->hasFile($field)) return null;

        $file     = $request->file($field);
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        Storage::disk('soal_images')->putFileAs('soal-images', $file, $filename);

        return $filename;
    }
}
