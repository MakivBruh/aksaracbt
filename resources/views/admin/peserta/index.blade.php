@extends('layouts.admin')

@section('title', 'Daftar Peserta')

@section('content')
<script>
    setTimeout(() => {
        if (document.activeElement?.closest('[data-peserta-search]')) {
            return;
        }

        window.location.reload();
    }, 10000);
</script>

<div class="flex flex-col gap-4 mb-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <p class="text-sm text-gray-500">
            @if($search)
                Ditemukan {{ $pesertas->total() }} peserta untuk pencarian "{{ $search }}".
            @else
                Total {{ $pesertas->total() }} peserta terdaftar.
            @endif
        </p>
        <p class="text-xs text-gray-400 mt-1">Monitoring otomatis refresh tiap 10 detik.</p>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <form method="GET" action="{{ route('admin.peserta.index') }}" class="flex w-full gap-2 sm:w-auto" data-peserta-search>
            <label for="q" class="sr-only">Cari peserta</label>
            <input
                id="q"
                name="q"
                type="search"
                value="{{ $search }}"
                placeholder="Cari no ujian, nama, email, sekolah..."
                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100 sm:w-80"
            >
            <button
                type="submit"
                class="rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-800"
            >
                Cari
            </button>
            @if($search)
                <a
                    href="{{ route('admin.peserta.index') }}"
                    class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50"
                >
                    Reset
                </a>
            @endif
        </form>

        <a href="{{ route('admin.peserta.import') }}"
           class="inline-flex justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
            Import Spreadsheet
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
            <tr>
                <th class="text-left px-4 py-3">No. Ujian</th>
                <th class="text-left px-4 py-3">Nama</th>
                <th class="text-left px-4 py-3">Sekolah</th>
                <th class="text-left px-4 py-3">Email</th>
                <th class="text-left px-4 py-3">Mapel</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="text-left px-4 py-3">Progress</th>
                <th class="text-center px-4 py-3">Pelanggaran</th>
                <th class="text-right px-4 py-3">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($pesertas as $p)
                @php
                    $jml = $totalPelanggaran[$p->id] ?? 0;
                    $progress = $progressByPeserta[$p->id] ?? ['terjawab' => 0, 'total' => 0, 'persen' => 0];
                    $kodeMapel = $pilihanMapelKodeByPeserta->get($p->id, collect());
                    $badge = match($p->status) {
                        'selesai' => 'bg-green-100 text-green-700',
                        'sedang_ujian' => 'bg-yellow-100 text-yellow-700',
                        default => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-xs">{{ $p->no_ujian }}</td>
                    <td class="px-4 py-3">{{ $p->nama }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $p->nama_sekolah ?: '-' }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $p->email }}</td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-1.5">
                            @forelse($kodeMapel as $kode)
                                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                                    {{ $kode }}
                                </span>
                            @empty
                                <span class="text-xs text-gray-400">-</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">
                            {{ str_replace('_', ' ', $p->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 min-w-48">
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                            <span>{{ $progress['terjawab'] }}/{{ $progress['total'] }} soal</span>
                            <span class="font-semibold text-gray-700">{{ $progress['persen'] }}%</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-600 rounded-full" style="width: {{ $progress['persen'] }}%"></div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="{{ $jml >= 3 ? 'text-red-600 font-bold' : 'text-gray-400' }}">
                            {{ $jml }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex justify-end gap-2">
                        @if($jml >= 3)
                            <form method="POST" action="{{ route('admin.peserta.unlock', $p->id) }}"
                                  onsubmit="return confirm('Buka akses ujian peserta ini dan reset pelanggarannya?')">
                                @csrf
                                <button class="text-xs bg-green-600 hover:bg-green-700 text-white font-semibold px-3 py-1.5 rounded-lg">
                                    Buka Akses
                                </button>
                            </form>
                        @endif
                            <form method="POST" action="{{ route('admin.peserta.destroy', $p->id) }}"
                                  onsubmit="return confirm(@js("Hapus akun peserta {$p->nama}? Semua jawaban, pelanggaran, nilai, dan mapel pilihan peserta ini juga akan dihapus. Tindakan ini tidak bisa dibatalkan."))">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs bg-red-600 hover:bg-red-700 text-white font-semibold px-3 py-1.5 rounded-lg">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-10 text-center text-gray-400">
                        @if($search)
                            Tidak ada peserta yang cocok dengan pencarian "{{ $search }}".
                        @else
                            Belum ada peserta. Import spreadsheet dulu di menu sebelah.
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $pesertas->links() }}</div>
@endsection
