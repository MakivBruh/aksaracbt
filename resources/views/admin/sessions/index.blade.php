@extends('layouts.admin')

@section('title', 'Token Tryout')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between gap-4">
        <div>
            <p class="text-sm text-gray-500">
                Token di sini berlaku untuk satu sesi tryout bersama. Timer peserta mengikuti jam mulai sesi.
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.sessions.display') }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Buka Layar Timer</a>
            <a href="{{ route('admin.sessions.create') }}" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Buat Token</a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-3">Nama</th>
                    <th class="text-left px-4 py-3">Token</th>
                    <th class="text-left px-4 py-3">Mulai</th>
                    <th class="text-left px-4 py-3">Selesai</th>
                    <th class="text-center px-4 py-3">Durasi</th>
                    <th class="text-center px-4 py-3">Status</th>
                    <th class="text-right px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($sessions as $session)
                    @php
                        $ended = $session->hasEnded();
                        $started = $session->hasStarted();
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $session->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $session->token }}</td>
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $session->starts_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $session->endsAt()->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3 text-center">{{ $session->duration_minutes }} menit</td>
                        <td class="px-4 py-3 text-center">
                            @if(! $session->is_active)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">nonaktif</span>
                            @elseif($ended)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">selesai</span>
                            @elseif($started)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">berjalan</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">belum mulai</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.sessions.edit', $session) }}"
                               class="text-xs text-blue-600 hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                            Belum ada token tryout. Buat token seperti AKUHEBAT terlebih dahulu.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $sessions->links() }}</div>
</div>
@endsection
