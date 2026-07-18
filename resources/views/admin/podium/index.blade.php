@extends('layouts.admin')

@section('title', 'Podium 15 Teratas')

@section('content')
<div id="podium-react-root"
     data-skor-url="{{ route('admin.skor.index') }}"></div>
<script id="podium-leaderboard-data" type="application/json">@json($leaderboard, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
@endsection

@push('scripts')
    @vite('resources/js/podium.jsx')
@endpush
