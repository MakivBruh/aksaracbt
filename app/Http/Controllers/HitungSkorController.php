<?php

namespace App\Http\Controllers;

use App\Services\HitungSkor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HitungSkorController extends Controller
{
    public function __construct(private HitungSkor $service) {}

    /** Tampilkan halaman rekap nilai (hitung ulang jika diminta). */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if ($request->boolean('hitung_ulang')) {
            $this->service->hitungSemuaPeserta();
            return redirect()->route('admin.skor.index', $request->only('q'))
                ->with('success', 'Skor berhasil dihitung ulang.');
        }

        $pesertaQuery = DB::connection('peserta_db')
            ->table('pesertas as p')
            ->where('p.status', 'selesai');

        if ($q !== '') {
            $pesertaQuery->where(function ($query) use ($q) {
                $query->where('p.no_ujian', 'like', "%{$q}%")
                    ->orWhere('p.nama', 'like', "%{$q}%")
                    ->orWhere('p.nama_sekolah', 'like', "%{$q}%")
                    ->orWhere('p.email', 'like', "%{$q}%");
            });
        }

        $pesertaPage = $pesertaQuery
            ->select('p.id')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('nilai_details as nd')
                    ->whereColumn('nd.peserta_id', 'p.id');
            })
            ->orderBy('p.no_ujian')
            ->paginate(50)
            ->withQueryString();

        $pesertaIds = collect($pesertaPage->items())->pluck('id');

        $rekap = DB::connection('peserta_db')
            ->table('pesertas as p')
            ->join('nilai_details as nd', 'p.id', '=', 'nd.peserta_id')
            ->join('mata_pelajarans as mp', 'nd.mata_pelajaran_id', '=', 'mp.id')
            ->whereIn('p.id', $pesertaIds)
            ->select(
                'p.id',
                'p.nama',
                'p.nama_sekolah',
                'p.email',
                'p.no_ujian',
                'mp.kode as mapel_kode',
                'mp.nama as mapel_nama',
                'mp.tipe as mapel_tipe',
                'nd.benar',
                'nd.salah',
                'nd.kosong',
                'nd.skor',
                'nd.poin_mentah',
                'nt.nilai_akhir',
            )
            ->leftJoin('nilai_totals as nt', 'p.id', '=', 'nt.peserta_id')
            ->orderBy('p.no_ujian')
            ->orderBy('mp.tipe', 'asc') // wajib dulu
            ->orderBy('mp.nama')
            ->get()
            ->groupBy('id'); // group per peserta

        $semuaMapel = DB::connection('peserta_db')
            ->table('mata_pelajarans')
            ->select('kode as mapel_kode', 'nama as mapel_nama', 'tipe as mapel_tipe')
            ->orderBy('tipe')
            ->orderBy('nama')
            ->get();

        return view('admin.skor.index', compact('rekap', 'q', 'pesertaPage', 'semuaMapel'));
    }

    /** Hitung skor satu peserta dan tampilkan hasilnya. */
    public function peserta(int $pesertaId)
    {
        $hasil = $this->service->hitungSatuPeserta($pesertaId);
        return response()->json($hasil);
    }

    public function show(int $pesertaId)
    {
        $peserta = DB::connection('peserta_db')
            ->table('pesertas')
            ->where('id', $pesertaId)
            ->first();

        abort_unless($peserta, 404);

        $mapelStats = DB::connection('peserta_db')
            ->table('nilai_details as nd')
            ->join('mata_pelajarans as mp', 'nd.mata_pelajaran_id', '=', 'mp.id')
            ->where('nd.peserta_id', $pesertaId)
            ->select(
                'mp.kode as mapel_kode',
                'mp.nama as mapel_nama',
                'mp.tipe as mapel_tipe',
                'nd.benar',
                'nd.salah',
                'nd.kosong',
                'nd.skor',
                'nd.poin_mentah',
            )
            ->orderBy('mp.tipe')
            ->orderBy('mp.nama')
            ->get()
            ->map(function ($row) {
                $totalSoal = (int) $row->benar + (int) $row->salah + (int) $row->kosong;
                $terjawab = (int) $row->benar + (int) $row->salah;
                $nilaiMaksimum = $row->mapel_tipe === 'pilihan' ? 150 : 100;
                $nilai = $nilaiMaksimum > 0 ? round(((float) $row->poin_mentah / $nilaiMaksimum) * 100, 2) : 0;

                return (object) [
                    'mapel_kode' => $row->mapel_kode,
                    'mapel_nama' => $row->mapel_nama,
                    'mapel_tipe' => $row->mapel_tipe,
                    'benar' => (int) $row->benar,
                    'salah' => (int) $row->salah,
                    'kosong' => (int) $row->kosong,
                    'terjawab' => $terjawab,
                    'total_soal' => $totalSoal,
                    'nilai' => $nilai,
                    'poin_mentah' => (float) $row->poin_mentah,
                ];
            });

        $nilaiTotal = DB::connection('peserta_db')->table('nilai_totals')->where('peserta_id', $pesertaId)->first();

        $totals = [
            'total_soal' => $mapelStats->sum('total_soal'),
            'terjawab' => $mapelStats->sum('terjawab'),
            'kosong' => $mapelStats->sum('kosong'),
            'benar' => $mapelStats->sum('benar'),
            'salah' => $mapelStats->sum('salah'),
            'nilai_rata_rata' => round($mapelStats->avg('nilai') ?? 0, 2),
            'total_nilai_mapel' => round($mapelStats->sum('nilai'), 2),
            'poin_mentah' => (float) ($nilaiTotal?->poin_mentah ?? 0),
            'nilai_akhir' => (float) ($nilaiTotal?->nilai_akhir ?? 0),
        ];

        return view('admin.skor.show', compact('peserta', 'mapelStats', 'totals'));
    }

    public function podium()
    {
        $leaderboard = $this->buildLeaderboard()->take(15)->values();

        return view('admin.podium.index', [
            'leaderboard' => $leaderboard,
            'juara' => $leaderboard->take(3)->values(),
            'harapan' => $leaderboard->slice(3, 3)->values(),
            'lainnya' => $leaderboard->slice(6)->values(),
        ]);
    }

    private function buildLeaderboard(): \Illuminate\Support\Collection
    {
        return DB::connection('peserta_db')
            ->table('pesertas as p')
            ->join('nilai_totals as nt', 'p.id', '=', 'nt.peserta_id')
            ->where('p.status', 'selesai')
            ->select(
                'p.id',
                'p.nama',
                'p.nama_sekolah',
                'p.email',
                'p.no_ujian',
                'p.selesai_ujian_at',
                'nt.nilai_akhir',
                'nt.poin_mentah',
                DB::raw('(SELECT COALESCE(SUM(nd.benar), 0) FROM nilai_details nd WHERE nd.peserta_id = p.id) as total_benar'),
                DB::raw('(SELECT COALESCE(SUM(nd.benar + nd.salah + nd.kosong), 0) FROM nilai_details nd WHERE nd.peserta_id = p.id) as total_soal'),
            )
            ->orderByDesc('nilai_akhir')
            ->orderByDesc('total_benar')
            ->orderBy('p.selesai_ujian_at')
            ->orderBy('p.no_ujian')
            ->limit(15)
            ->get()
            ->map(function ($row) {
                $row->nilai_akhir = (float) $row->nilai_akhir;
                $row->total_benar = (int) $row->total_benar;
                $row->total_soal = (int) $row->total_soal;
                $row->poin_mentah = (float) $row->poin_mentah;

                return $row;
            });
    }
}
