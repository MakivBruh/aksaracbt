@extends('layouts.exam')

@section('title', 'Ujian Selesai – TKA CBT')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-green-600 to-green-800 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-10 text-center">

        <div class="text-7xl mb-6">🎉</div>

        <h1 class="text-2xl font-bold text-gray-800 mb-2">Ujian Selesai!</h1>
        <p class="text-gray-500 mb-8">
            Jawaban kamu sudah berhasil dikumpulkan. Hasil akan diumumkan oleh panitia.
        </p>

        <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-800 text-sm">
            Kamu boleh menutup browser ini sekarang.
        </div>
    </div>
</div>
@endsection
