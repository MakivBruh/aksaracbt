@extends('layouts.admin')

@section('title', 'Tambah Peserta')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="rounded-xl bg-white p-6 shadow-sm sm:p-8">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Tambah Peserta Manual</h3>
            <p class="mt-1 text-sm text-gray-500">Nomor ujian dibuat otomatis. Peserta tetap login memakai email dan token tryout yang aktif.</p>
        </div>

        <form method="POST" action="{{ route('admin.peserta.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="nama" class="mb-1 block text-sm font-semibold text-gray-700">Nama lengkap *</label>
                <input id="nama" name="nama" value="{{ old('nama') }}" required maxlength="255" autofocus
                       class="w-full rounded-xl border border-gray-200 px-4 py-2.5 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="nama_sekolah" class="mb-1 block text-sm font-semibold text-gray-700">Nama sekolah</label>
                <input id="nama_sekolah" name="nama_sekolah" value="{{ old('nama_sekolah') }}" maxlength="255"
                       class="w-full rounded-xl border border-gray-200 px-4 py-2.5 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                @error('nama_sekolah') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-semibold text-gray-700">Email *</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="255"
                       class="w-full rounded-xl border border-gray-200 px-4 py-2.5 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach([0, 1] as $index)
                    <div>
                        <label for="mapel-{{ $index }}" class="mb-1 block text-sm font-semibold text-gray-700">Mapel pilihan {{ $index + 1 }} *</label>
                        <select id="mapel-{{ $index }}" name="mapel_pilihan[]" required
                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                            <option value="">-- Pilih mapel --</option>
                            @foreach($mapels as $mapel)
                                <option value="{{ $mapel->id }}" @selected((string) old("mapel_pilihan.{$index}") === (string) $mapel->id)>{{ $mapel->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>
            @error('mapel_pilihan') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            @error('mapel_pilihan.*') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

            <div class="flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-end">
                <a href="{{ route('admin.peserta.index') }}" class="rounded-xl border border-gray-200 px-5 py-2.5 text-center text-sm font-semibold text-gray-600 hover:bg-gray-50">Batal</a>
                <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Simpan Peserta</button>
            </div>
        </form>
    </div>
</div>
@endsection
