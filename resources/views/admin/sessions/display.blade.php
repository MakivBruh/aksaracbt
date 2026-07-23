<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Layar Timer - Aksara CBT</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @vite(['resources/css/app.css', 'resources/js/session-display.jsx'])
</head>
<body class="m-0 overflow-hidden bg-slate-950">
    <div id="session-display-root"></div>
    <script id="session-display-data" type="application/json">@json($displayPayload)</script>
</body>
</html>
