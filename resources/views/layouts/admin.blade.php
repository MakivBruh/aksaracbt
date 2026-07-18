<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Aksara CBT')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-gray-100 min-h-screen flex">

@php
    $navItems = [
        ['label' => 'Daftar Peserta', 'route' => 'admin.peserta.index', 'active' => 'admin.peserta.index'],
        ['label' => 'Import Excel', 'route' => 'admin.peserta.import', 'active' => 'admin.peserta.import'],
        ['label' => 'Token Tryout', 'route' => 'admin.sessions.index', 'active' => 'admin.sessions*'],
        ['label' => 'Bank Soal', 'route' => 'admin.soal.index', 'active' => 'admin.soal*'],
        ['label' => 'Skor Peserta', 'route' => 'admin.skor.index', 'active' => 'admin.skor*'],
        ['label' => 'Podium', 'route' => 'admin.podium.index', 'active' => 'admin.podium*'],
    ];
@endphp

<aside class="w-56 bg-slate-800 text-white flex flex-col min-h-screen flex-shrink-0">
    <div class="px-5 py-5 border-b border-slate-700">
        <h1 class="font-bold text-lg">Aksara CBT</h1>
        <p class="text-slate-400 text-xs mt-0.5">Panel Admin Terpadu</p>
    </div>

    <nav class="flex-1 py-4 space-y-1 px-3">
        @foreach($navItems as $item)
            <a href="{{ route($item['route']) }}"
               class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm hover:bg-slate-700 transition-colors {{ request()->routeIs($item['active']) ? 'bg-slate-700' : '' }}">
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="px-3 py-4 border-t border-slate-700">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="text-sm text-slate-300 hover:text-white w-full text-left px-3 py-2">
                Logout
            </button>
        </form>
        <p class="mt-3 text-xs text-slate-400 px-3">
            Peserta, soal, skor, dan podium berada dalam satu project.
        </p>
    </div>
</aside>

<div class="flex-1 flex flex-col min-h-screen">
    <header class="bg-white shadow-sm px-6 py-4">
        <h2 class="font-semibold text-gray-800">@yield('title', 'Dashboard')</h2>
    </header>

    <main class="flex-1 p-6">
        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('warnings') && count(session('warnings')))
            <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg px-4 py-3 text-sm">
                <p class="font-semibold mb-1">Perhatian saat import:</p>
                <ul class="list-disc ml-4 space-y-0.5">
                    @foreach(session('warnings') as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js"></script>
<script>
    window._katexReady = typeof window.renderMathInElement === 'function';
</script>
@stack('scripts')
</body>
</html>
