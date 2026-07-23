<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\PesertaImport;
use App\Models\Peserta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * CATATAN ARSITEKTUR:
 * Controller ini sengaja LIVE di peserta-app, bukan di admin-app.
 * Alasannya: peserta-app punya akses tulis ke database-nya sendiri,
 * sedangkan admin-app cuma dikasih koneksi READ-ONLY ke peserta_db
 * (lihat admin-app/config/database.php). Kalau import/reset-token
 * ditaruh di admin-app, dia butuh akses tulis ke peserta_db —
 * itu melanggar prinsip keamanan yang sudah disepakati.
 *
 * Jadi alurnya: panitia buka /admin di PESERTA-APP untuk kelola akun,
 * lalu buka admin-app (aplikasi terpisah) untuk input soal & lihat skor.
 *
 * Route ini WAJIB di-protect middleware auth (Laravel Breeze/Fortify)
 * dengan akun panitia — jangan biarkan terbuka publik.
 */
class PesertaImportController extends Controller
{
    /** Daftar peserta + jumlah pelanggaran (buat dipantau panitia). */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $pesertas = Peserta::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('no_ujian', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nama_sekolah', 'like', "%{$search}%");
                });
            })
            ->orderBy('no_ujian')
            ->paginate(50)
            ->withQueryString();
        $pesertaIds = collect($pesertas->items())->pluck('id')->values();

        $totalPelanggaran = DB::table('log_pelanggarans')
            ->select('peserta_id', DB::raw('COUNT(*) as total'))
            ->whereIn('peserta_id', $pesertaIds)
            ->groupBy('peserta_id')
            ->pluck('total', 'peserta_id');

        $wajibMapelIds = DB::table('mata_pelajarans')
            ->where('tipe', 'wajib')
            ->pluck('id');

        $pilihanMapelByPeserta = DB::table('peserta_mata_pelajaran')
            ->whereIn('peserta_id', $pesertaIds)
            ->get()
            ->groupBy('peserta_id')
            ->map(fn($rows) => $rows->pluck('mata_pelajaran_id'));

        $pilihanMapelKodeByPeserta = DB::table('peserta_mata_pelajaran as pmp')
            ->join('mata_pelajarans as mp', 'pmp.mata_pelajaran_id', '=', 'mp.id')
            ->whereIn('pmp.peserta_id', $pesertaIds)
            ->where('mp.tipe', 'pilihan')
            ->select('pmp.peserta_id', 'mp.kode')
            ->orderBy('mp.kode')
            ->get()
            ->groupBy('peserta_id')
            ->map(fn($rows) => $rows->pluck('kode')->values());

        $soalCountByMapel = DB::table('soals')
            ->select('mata_pelajaran_id', DB::raw('COUNT(*) as total'))
            ->groupBy('mata_pelajaran_id')
            ->pluck('total', 'mata_pelajaran_id');

        $answeredByPeserta = DB::table('jawabans')
            ->whereIn('peserta_id', $pesertaIds)
            ->where(function ($query) {
                $query->whereNotNull('jawaban')->orWhereNotNull('jawaban_data');
            })
            ->select('peserta_id', DB::raw('COUNT(*) as total'))
            ->groupBy('peserta_id')
            ->pluck('total', 'peserta_id');

        $progressByPeserta = [];

        foreach ($pesertas as $peserta) {
            $mapelIds = $wajibMapelIds
                ->merge($pilihanMapelByPeserta->get($peserta->id, collect()))
                ->unique();

            $totalSoal = $mapelIds->sum(fn($mapelId) => (int) ($soalCountByMapel[$mapelId] ?? 0));
            $terjawab = min((int) ($answeredByPeserta[$peserta->id] ?? 0), $totalSoal);

            $progressByPeserta[$peserta->id] = [
                'terjawab' => $terjawab,
                'total' => $totalSoal,
                'persen' => $totalSoal > 0 ? (int) round(($terjawab / $totalSoal) * 100) : 0,
            ];
        }

        return view('admin.peserta.index', compact(
            'pesertas',
            'search',
            'totalPelanggaran',
            'progressByPeserta',
            'pilihanMapelKodeByPeserta',
        ));
    }

    public function importForm()
    {
        return view('admin.peserta.import');
    }

    public function create()
    {
        $mapels = \App\Models\MataPelajaran::pilihan()->orderBy('nama')->get();

        return view('admin.peserta.create', compact('mapels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nama_sekolah' => 'nullable|string|max:255',
            'email' => 'required|email:rfc|max:255|unique:peserta_db.pesertas,email',
            'mapel_pilihan' => 'required|array|size:2',
            'mapel_pilihan.*' => [
                'required', 'integer', 'distinct',
                Rule::exists('peserta_db.mata_pelajarans', 'id')->where(fn ($query) => $query->where('tipe', 'pilihan')),
            ],
        ], [
            'mapel_pilihan.size' => 'Pilih tepat dua mata pelajaran pilihan.',
            'mapel_pilihan.*.distinct' => 'Dua mata pelajaran pilihan harus berbeda.',
        ]);

        $email = strtolower(trim($validated['email']));
        $noUjian = 'TKA-'.strtoupper(substr(md5($email), 0, 6));

        if (Peserta::where('no_ujian', $noUjian)->exists()) {
            $noUjian = 'TKA-'.strtoupper(Str::random(6));
        }

        $peserta = DB::connection('peserta_db')->transaction(function () use ($validated, $email, $noUjian) {
            $peserta = Peserta::create([
                'nama' => trim($validated['nama']),
                'nama_sekolah' => filled($validated['nama_sekolah'] ?? null) ? trim($validated['nama_sekolah']) : null,
                'email' => $email,
                'no_ujian' => $noUjian,
                'token_login' => Str::random(32),
                'status' => 'belum_mulai',
                'active_session_token' => null,
            ]);
            $peserta->mataPelajarans()->sync($validated['mapel_pilihan']);

            return $peserta;
        });

        return redirect()->route('admin.peserta.index')
            ->with('success', "Peserta {$peserta->nama} berhasil ditambahkan dengan nomor ujian {$peserta->no_ujian}.");
    }

    /**
     * Proses import spreadsheet: nama | nama_sekolah | email | mapel_pilihan_1 | mapel_pilihan_2
     */
    public function importStore(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx|max:5120',
        ]);

        $import = new PesertaImport();
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'xlsx') {
            $import->importFromXlsx($file->getRealPath());
        } else {
            $import->importFromCsv($file->getRealPath());
        }

        $msg = "Import selesai: {$import->getImported()} peserta berhasil.";

        if ($import->getSkipped() > 0) {
            $msg .= " {$import->getSkipped()} baris dilewati.";
        }

        return redirect()->route('admin.peserta.index')
            ->with('success', $msg)
            ->with('warnings', $import->getWarnings());
    }

    /** Generate ulang token_login peserta (misal karena lupa/hilang). */
    public function resetToken(Peserta $peserta): RedirectResponse
    {
        $peserta->update([
            'token_login' => Str::random(32),
            'token_used_at' => null,
            'active_session_token' => null,
        ]);

        return back()->with('success', "Token {$peserta->no_ujian} berhasil di-reset: {$peserta->token_login}");
    }

    public function unlock(Peserta $peserta): RedirectResponse
    {
        DB::table('log_pelanggarans')
            ->where('peserta_id', $peserta->id)
            ->delete();

        $peserta->update([
            'status' => 'sedang_ujian',
            'selesai_ujian_at' => null,
            'active_session_token' => null,
        ]);

        return back()->with('success', "Akses ujian {$peserta->nama} sudah dibuka lagi. Peserta bisa login ulang selama token tryout masih aktif.");
    }

    public function updateStatus(Request $request, Peserta $peserta): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:belum_mulai,sedang_ujian,selesai',
        ]);

        $status = $validated['status'];
        $attributes = ['status' => $status, 'active_session_token' => null];

        if ($status === 'selesai') {
            $attributes['selesai_ujian_at'] = $peserta->selesai_ujian_at ?: now();
        } elseif ($status === 'sedang_ujian') {
            $attributes['selesai_ujian_at'] = null;
        } else {
            $attributes += [
                'mulai_ujian_at' => null,
                'selesai_ujian_at' => null,
                'token_used_at' => null,
                'active_exam_session_id' => null,
            ];
        }

        $peserta->update($attributes);

        return back()->with('success', "Status {$peserta->nama} diubah menjadi ".str_replace('_', ' ', $status).'. Jawaban yang sudah tersimpan tetap dipertahankan.');
    }

    public function finishAll(): RedirectResponse
    {
        $now = now();
        $total = Peserta::where('status', '!=', 'selesai')->update([
            'status' => 'selesai',
            'selesai_ujian_at' => $now,
            'active_session_token' => null,
            'updated_at' => $now,
        ]);

        return back()->with('success', "{$total} peserta berhasil diselesaikan. Buka halaman Skor lalu tekan Hitung Ulang untuk menghitung nilainya.");
    }

    public function destroy(Peserta $peserta): RedirectResponse
    {
        $nama = $peserta->nama;
        $noUjian = $peserta->no_ujian;

        DB::connection('peserta_db')->transaction(function () use ($peserta) {
            $db = DB::connection('peserta_db');
            $db->table('jawabans')->where('peserta_id', $peserta->id)->delete();
            $db->table('log_pelanggarans')->where('peserta_id', $peserta->id)->delete();
            $db->table('nilai_details')->where('peserta_id', $peserta->id)->delete();
            $db->table('peserta_mata_pelajaran')->where('peserta_id', $peserta->id)->delete();

            if ($db->getSchemaBuilder()->hasTable('personal_access_tokens')) {
                $db->table('personal_access_tokens')
                    ->where('tokenable_type', Peserta::class)
                    ->where('tokenable_id', $peserta->id)
                    ->delete();
            }

            $peserta->delete();
        });

        return back()->with('success', "Akun peserta {$nama} ({$noUjian}) berhasil dihapus.");
    }
}
