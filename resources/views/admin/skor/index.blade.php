@extends('layouts.admin')

@section('title', 'Rekap Nilai Peserta')

@section('content')
<div class="space-y-4">
    <div class="rounded-xl bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm text-gray-500">
                    {{ $pesertaPage->total() }} peserta selesai memiliki rekap nilai.
                    Halaman ini menampilkan {{ $rekap->count() }} peserta.
                </p>
                <p class="mt-1 text-xs text-gray-400">Cari berdasarkan no ujian, nama, sekolah, atau email peserta.</p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <form method="GET" action="{{ route('admin.skor.index') }}" class="flex flex-col gap-2 sm:flex-row">
                    <input type="search"
                           name="q"
                           value="{{ $q }}"
                           placeholder="Cari peserta..."
                           class="w-full rounded-xl border border-gray-300 px-4 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 sm:w-80">
                    <button class="rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                        Cari
                    </button>
                    @if($q !== '')
                        <a href="{{ route('admin.skor.index') }}"
                           class="rounded-xl border border-gray-200 px-4 py-2 text-center text-sm font-semibold text-gray-600 hover:bg-gray-50">
                            Reset
                        </a>
                    @endif
                </form>

                <a href="{{ route('admin.skor.index', array_filter(['hitung_ulang' => 1, 'q' => $q])) }}"
                   onclick="return confirm('Hitung ulang skor semua peserta? Ini akan override nilai lama.')"
                   class="rounded-xl bg-blue-600 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-blue-700">
                    Hitung Ulang Semua
                </a>
            </div>
        </div>
    </div>

    @if($rekap->isEmpty())
        <div class="rounded-xl bg-white p-12 text-center text-gray-400">
            Belum ada peserta yang cocok, belum selesai ujian, atau skor belum pernah dihitung.
        </div>
    @else
        <div class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                        <tr>
                            <th class="sticky left-0 z-10 bg-gray-50 px-4 py-3 text-left">No. Ujian</th>
                            <th class="px-4 py-3 text-left">Nama</th>
                            <th class="px-4 py-3 text-left">Sekolah</th>
                            <th class="px-4 py-3 text-left">Email</th>
                            @foreach($semuaMapel as $mapel)
                                <th class="px-4 py-3 text-center whitespace-nowrap">{{ $mapel->mapel_kode }}</th>
                            @endforeach
                            <th class="px-4 py-3 text-center bg-blue-50 font-bold text-blue-700">Nilai Akhir</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($rekap as $pesertaId => $rows)
                            @php
                                $rowByKode = $rows->keyBy('mapel_kode');
                                $peserta = $rows->first();
                                $nilaiAkhir = (float) ($peserta->nilai_akhir ?? 0);
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="sticky left-0 bg-white px-4 py-3 font-mono text-xs">{{ $peserta->no_ujian }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $peserta->nama }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $peserta->nama_sekolah ?: '-' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $peserta->email }}</td>

                                @foreach($semuaMapel as $mapel)
                                    @php
                                        $row = $rowByKode->get($mapel->mapel_kode);
                                        $totalSoal = $row ? $row->benar + $row->salah + $row->kosong : 0;
                                        $maksimum = $mapel->mapel_tipe === 'pilihan' ? 150 : 100;
                                        $nilai = $row ? round(((float) $row->poin_mentah / $maksimum) * 100, 1) : null;
                                    @endphp
                                    <td class="px-4 py-3 text-center">
                                        @if($row)
                                            <div class="font-semibold text-gray-900">{{ number_format($nilai, 1) }}</div>
                                            <div class="text-xs text-gray-400">{{ number_format($row->poin_mentah, 2) }} poin</div>
                                        @else
                                            <span class="text-gray-300">-</span>
                                        @endif
                                    </td>
                                @endforeach

                                <td class="bg-blue-50 px-4 py-3 text-center font-bold text-blue-700">
                                    {{ number_format($nilaiAkhir, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.skor.show', $pesertaId) }}"
                                       class="inline-flex rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                                        Lihat Statistik
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $pesertaPage->links() }}
        </div>
    @endif
</div>
@endsection
