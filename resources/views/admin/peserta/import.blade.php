@extends('layouts.admin')

@section('title', 'Import Peserta')

@section('content')
<div class="max-w-3xl">
    <div class="bg-white rounded-xl shadow-sm p-8">
        <h3 class="font-semibold text-gray-800 mb-2">Import Data Peserta</h3>
        <p class="text-sm text-gray-500 mb-6">
            Upload file Excel (.xlsx) atau CSV. Import bersifat idempotent:
            peserta yang sudah ada akan di-update berdasarkan email, tidak duplikat.
        </p>

        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 text-sm">
            <p class="font-semibold text-blue-800 mb-2">Format kolom yang didukung:</p>
            <div class="overflow-x-auto">
                <table class="text-xs border-collapse w-full">
                    <thead>
                        <tr class="bg-blue-100">
                            <th class="border border-blue-200 px-3 py-1 text-left">nama</th>
                            <th class="border border-blue-200 px-3 py-1 text-left">nama_sekolah</th>
                            <th class="border border-blue-200 px-3 py-1 text-left">email</th>
                            <th class="border border-blue-200 px-3 py-1 text-left">mapel_pilihan_1</th>
                            <th class="border border-blue-200 px-3 py-1 text-left">mapel_pilihan_2</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="text-blue-700">
                            <td class="border border-blue-200 px-3 py-1">Andi Saputra</td>
                            <td class="border border-blue-200 px-3 py-1">SMA Aksara 1</td>
                            <td class="border border-blue-200 px-3 py-1">andi@email.com</td>
                            <td class="border border-blue-200 px-3 py-1">Fisika</td>
                            <td class="border border-blue-200 px-3 py-1">Kimia</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-blue-600 mt-2">
                Nama mapel pilihan:
                <strong>Matematika Tingkat Lanjut, Fisika, Kimia, Biologi,
                Ekonomi, Geografi, Sosiologi, Sejarah</strong>.
                Nama lama <strong>Matematika Lanjutan</strong> tetap diterima saat import.
            </p>
            <p class="text-blue-600 mt-1">
                Header sekolah yang diterima: <strong>nama_sekolah</strong>, sekolah,
                asal_sekolah, instansi, school, atau school_name.
            </p>
        </div>

        <form method="POST"
              action="{{ route('admin.peserta.import.store') }}"
              enctype="multipart/form-data"
              class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">File XLSX atau CSV</label>
                <input type="file"
                       name="file"
                       accept=".xlsx,.csv,.txt"
                       required
                       class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer">
                @error('file')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl">
                Upload & Import
            </button>
        </form>

        <div class="mt-6 text-sm text-gray-400 space-y-1">
            <p>- No. ujian di-generate otomatis dari email (TKA-XXXXXX).</p>
            <p>- Token login di-generate acak per peserta.</p>
            <p>- Lihat daftar token di halaman <a href="{{ route('admin.peserta.index') }}" class="text-blue-600 underline">Peserta</a>.</p>
        </div>
    </div>
</div>
@endsection
