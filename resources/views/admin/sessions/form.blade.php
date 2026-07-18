@php
    $startsAtValue = old('starts_at');

    if (! $startsAtValue && $session?->starts_at) {
        $startsAtValue = $session->starts_at->format('Y-m-d\TH:i');
    }
@endphp

<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm p-8">
        <form method="POST" action="{{ $action }}" class="space-y-5">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Sesi</label>
                <input name="name"
                       value="{{ old('name', $session?->name ?? 'Tryout Aksara') }}"
                       required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Token Peserta</label>
                <input name="token"
                       value="{{ old('token', $session?->token ?? 'AKUHEBAT') }}"
                       required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono uppercase">
                <p class="text-xs text-gray-400 mt-1">Contoh: AKUHEBAT. Peserta memakai token ini bersama email masing-masing.</p>
                @error('token') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jam Mulai</label>
                    <input name="starts_at"
                           type="datetime-local"
                           value="{{ $startsAtValue }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 mt-1">Mengikuti timezone aplikasi: Asia/Jakarta.</p>
                    @error('starts_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Durasi</label>
                    <input name="duration_minutes"
                           type="number"
                           min="1"
                           max="600"
                           value="{{ old('duration_minutes', $session?->duration_minutes ?? 120) }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 mt-1">Kalau peserta telat, waktu tetap dihitung dari jam mulai.</p>
                    @error('duration_minutes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox"
                       name="is_active"
                       value="1"
                       class="rounded border-gray-300"
                       @checked(old('is_active', $session?->is_active ?? true))>
                Token aktif
            </label>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.sessions.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Batal</a>
                <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-3 rounded-xl">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>
