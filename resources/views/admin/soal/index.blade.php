@extends('layouts.admin')

@section('title', 'Bank Soal')

@section('content')
@php
    $totalFiltered = $soals->flatten(1)->count();
@endphp

<div class="space-y-5">
    <section class="rounded-2xl bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-sm text-gray-500">Total {{ $totalSoal }} soal tersimpan.</p>
                <h3 class="mt-1 text-xl font-semibold text-gray-900">Bank Soal per Mata Pelajaran</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Soal dikelompokkan berdasarkan mapel supaya lebih mudah dicek setelah import besar.
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <form method="POST"
                      action="{{ route('admin.soal.sync') }}"
                      onsubmit="return confirm('Sinkronkan bank soal admin ke halaman ujian peserta? Soal lama yang sudah tidak ada di admin akan dihapus dari tampilan ujian.')">
                    @csrf
                    <button class="w-full rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 sm:w-auto">
                        Sinkronkan ke Ujian
                    </button>
                </form>
                <a href="{{ route('admin.soal.create') }}"
                   class="rounded-xl bg-blue-600 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-blue-700">
                    + Tambah Soal
                </a>
            </div>
        </div>

        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($mapels as $mapel)
                @php
                    $count = (int) ($jumlahSoalByMapel[$mapel->id] ?? 0);
                    $active = (int) $mapelId === (int) $mapel->id;
                @endphp
                <a href="{{ route('admin.soal.index', array_filter(['mapel_id' => $mapel->id, 'q' => $q])) }}"
                   class="rounded-xl border p-4 transition hover:border-blue-200 hover:bg-blue-50/50 {{ $active ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-100' : 'border-gray-200 bg-white' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $mapel->tipe }}</p>
                            <h4 class="mt-1 truncate font-semibold text-gray-900">{{ $mapel->nama }}</h4>
                        </div>
                        <span class="rounded-full bg-gray-900 px-2.5 py-1 text-xs font-bold text-white">{{ $mapel->kode }}</span>
                    </div>
                    <p class="mt-3 text-2xl font-bold text-gray-900">{{ $count }}</p>
                    <p class="text-xs text-gray-500">soal tersimpan</p>
                </a>
            @endforeach
        </div>
    </section>

    <section class="rounded-2xl bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.soal.index') }}" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_18rem_auto_auto] lg:items-end">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Cari soal</label>
                <input type="search"
                       name="q"
                       value="{{ $q }}"
                       placeholder="Cari teks soal, opsi, atau nomor soal..."
                       class="w-full rounded-xl border border-gray-300 px-4 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Filter mapel</label>
                <select name="mapel_id"
                        class="w-full rounded-xl border border-gray-300 px-4 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    <option value="">Semua mapel</option>
                    @foreach($mapels as $mapel)
                        <option value="{{ $mapel->id }}" @selected((int) $mapelId === (int) $mapel->id)>
                            {{ $mapel->kode }} - {{ $mapel->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button class="rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                Terapkan
            </button>
            @if($q !== '' || $mapelId)
                <a href="{{ route('admin.soal.index') }}"
                   class="rounded-xl border border-gray-200 px-4 py-2 text-center text-sm font-semibold text-gray-600 hover:bg-gray-50">
                    Reset
                </a>
            @endif
        </form>
        <p class="mt-3 text-sm text-gray-500">
            Menampilkan {{ $totalFiltered }} soal{{ $selectedMapel ? ' untuk '.$selectedMapel->kode.' - '.$selectedMapel->nama : '' }}.
        </p>
    </section>

    @if($totalFiltered === 0)
        <section class="rounded-2xl bg-white p-12 text-center text-gray-400 shadow-sm">
            Belum ada soal yang cocok dengan filter ini.
        </section>
    @else
        @foreach($mapels as $mapel)
            @php
                $rows = $soals->get($mapel->id, collect());
            @endphp

            @continue($rows->isEmpty())

            <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
                <div class="border-b border-gray-100 bg-gray-50 px-5 py-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-700">{{ $mapel->kode }}</span>
                                <h3 class="font-semibold text-gray-900">{{ $mapel->nama }}</h3>
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">{{ $rows->count() }} soal</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">{{ ucfirst($mapel->tipe) }} - urut berdasarkan nomor soal.</p>
                        </div>
                        <a href="{{ route('admin.soal.create', ['mapel_id' => $mapel->id]) }}"
                           class="text-sm font-semibold text-blue-600 hover:text-blue-700">
                            Tambah soal {{ $mapel->kode }}
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-white text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="w-20 px-5 py-3 text-left">No.</th>
                                <th class="px-5 py-3 text-left">Ringkasan Soal</th>
                                <th class="w-24 px-5 py-3 text-center">Media</th>
                                <th class="w-24 px-5 py-3 text-center">Kunci</th>
                                <th class="w-36 px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($rows as $s)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-3 font-mono text-xs text-gray-500">
                                        {{ $s->nomor_urut }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-gray-900">
                                            {{ Str::limit(strip_tags($s->teks_soal ?: '(soal berupa gambar)'), 120) }}
                                        </p>
                                        <div class="mt-1 flex flex-wrap gap-2 text-xs text-gray-400">
                                            <span>Tipe opsi: {{ $s->tipe_opsi }}</span>
                                            @if($s->updated_at)
                                                <span>Update: {{ $s->updated_at->format('d/m/Y H:i') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        @if($s->gambar_soal)
                                            <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Ada</span>
                                        @else
                                            <span class="text-gray-300">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-green-100 text-sm font-bold text-green-700">
                                            {{ $s->kunci_jawaban }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.soal.edit', $s) }}"
                                               class="rounded-lg border border-blue-200 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50">
                                                Edit
                                            </a>
                                            <form method="POST"
                                                  action="{{ route('admin.soal.destroy', $s) }}"
                                                  onsubmit="return confirm('Hapus soal nomor {{ $s->nomor_urut }} dari {{ $mapel->kode }}? Tindakan tidak bisa dibatalkan.')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    @endif
</div>
@endsection
