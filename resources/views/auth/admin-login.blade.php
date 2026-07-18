<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin Peserta - Aksara</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="min-h-screen flex items-center justify-center p-4">
        <form method="POST" action="{{ route('login.store') }}" class="w-full max-w-sm rounded-xl bg-white p-6 shadow">
            @csrf

            <div class="mb-6">
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Aksara</p>
                <h1 class="mt-1 text-2xl font-bold">Login Admin Peserta</h1>
                <p class="mt-1 text-sm text-slate-500">Untuk import peserta dan melihat token login.</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <label class="block text-sm font-medium" for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="mt-1 w-full rounded border border-slate-300 px-3 py-2 outline-none focus:border-blue-500">

            <label class="mt-4 block text-sm font-medium" for="password">Password</label>
            <input id="password" name="password" type="password" required
                   class="mt-1 w-full rounded border border-slate-300 px-3 py-2 outline-none focus:border-blue-500">

            <label class="mt-4 flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300">
                Ingat saya
            </label>

            <button class="mt-6 w-full rounded bg-blue-700 px-4 py-2 font-semibold text-white hover:bg-blue-800">
                Masuk
            </button>
        </form>
    </main>
</body>
</html>
