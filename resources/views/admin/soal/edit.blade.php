@extends('layouts.admin')
@section('title', 'Edit Soal #' . $soal->nomor_urut)
@section('content')
<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    <div class="rounded-xl bg-white p-6 shadow-sm">
        <h3 class="mb-5 font-semibold text-gray-800">Edit Soal</h3>
        <form method="POST" action="{{ route('admin.soal.update', $soal) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf @method('PUT')
            @include('admin.soal._form-fields')
            <div class="flex gap-3"><button class="flex-1 rounded-xl bg-blue-600 py-3 font-semibold text-white">Simpan Perubahan</button><a href="{{ route('admin.soal.index') }}" class="rounded-xl border px-5 py-3">Batal</a></div>
        </form>
    </div>
    <div id="question-preview" class="rounded-xl bg-white p-6 shadow-sm xl:sticky xl:top-6 xl:self-start"><h3 class="mb-4 font-semibold">Preview Live</h3><div id="preview-soal" class="whitespace-pre-wrap leading-7"></div><div id="preview-table" class="mt-4"></div><div id="preview-answers" class="mt-5 space-y-2"></div></div>
</div>
@endsection
@push('styles') @include('admin.soal._form-assets', ['onlyStyles' => true]) @endpush
@push('scripts') @include('admin.soal._form-assets') @endpush
