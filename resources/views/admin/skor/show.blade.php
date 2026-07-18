@extends('layouts.admin')

@section('title', 'Statistik Nilai Peserta')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.skor.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">
                Kembali ke rekap nilai
            </a>
            <h3 class="mt-2 text-xl font-semibold text-gray-900">{{ $peserta->nama }}</h3>
            <p class="text-sm text-gray-500">{{ $peserta->no_ujian }} - {{ $peserta->email }}</p>
            <p class="text-sm text-gray-500">{{ $peserta->nama_sekolah ?: 'Sekolah belum diisi' }}</p>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-400">Total Soal</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $totals['total_soal'] }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-400">Menjawab</p>
            <p class="mt-2 text-2xl font-bold text-blue-700">{{ $totals['terjawab'] }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-400">Tidak Dijawab</p>
            <p class="mt-2 text-2xl font-bold text-gray-700">{{ $totals['kosong'] }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-400">Benar</p>
            <p class="mt-2 text-2xl font-bold text-green-700">{{ $totals['benar'] }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-400">Salah</p>
            <p class="mt-2 text-2xl font-bold text-red-700">{{ $totals['salah'] }}</p>
        </div>
        <div class="rounded-xl bg-blue-600 p-4 text-white shadow-sm">
            <p class="text-xs uppercase tracking-wide text-blue-100">Nilai Akhir</p>
            <p class="mt-2 text-2xl font-bold">{{ number_format($totals['nilai_rata_rata'], 2) }}</p>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_280px]">
        <div class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="border-b px-4 py-3">
                <h4 class="font-semibold text-gray-900">Statistik Per Mata Pelajaran</h4>
                <p class="mt-1 text-sm text-gray-500">Nilai mapel dihitung dari benar / total soal x 100.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Mapel</th>
                            <th class="px-4 py-3 text-center">Total</th>
                            <th class="px-4 py-3 text-center">Menjawab</th>
                            <th class="px-4 py-3 text-center">Tidak Dijawab</th>
                            <th class="px-4 py-3 text-center">Benar</th>
                            <th class="px-4 py-3 text-center">Salah</th>
                            <th class="px-4 py-3 text-center">Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($mapelStats as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-gray-900">{{ $row->mapel_nama }}</p>
                                    <p class="text-xs text-gray-400">{{ $row->mapel_kode }} · {{ ucfirst($row->mapel_tipe) }}</p>
                                </td>
                                <td class="px-4 py-3 text-center">{{ $row->total_soal }}</td>
                                <td class="px-4 py-3 text-center text-blue-700 font-semibold">{{ $row->terjawab }}</td>
                                <td class="px-4 py-3 text-center">{{ $row->kosong }}</td>
                                <td class="px-4 py-3 text-center text-green-700 font-semibold">{{ $row->benar }}</td>
                                <td class="px-4 py-3 text-center text-red-700 font-semibold">{{ $row->salah }}</td>
                                <td class="px-4 py-3 text-center font-bold text-gray-900">{{ number_format($row->nilai, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                                    Skor peserta ini belum tersedia. Coba hitung ulang skor terlebih dahulu.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm">
            <h4 class="font-semibold text-gray-900">Ringkasan Nilai</h4>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-gray-500">Rata-rata per mapel</dt>
                    <dd class="font-bold text-blue-700">{{ number_format($totals['nilai_rata_rata'], 2) }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-gray-500">Total nilai mapel</dt>
                    <dd class="font-semibold text-gray-900">{{ number_format($totals['total_nilai_mapel'], 2) }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-gray-500">Jumlah mapel</dt>
                    <dd class="font-semibold text-gray-900">{{ $mapelStats->count() }}</dd>
                </div>
            </dl>

            <div class="mt-5 rounded-xl bg-blue-50 p-4 text-sm text-blue-700">
                Nilai akhir memakai rata-rata nilai seluruh mapel agar peserta dengan pilihan mapel berbeda tetap dibandingkan secara adil.
            </div>
        </div>
    </div>
</div>
@endsection
