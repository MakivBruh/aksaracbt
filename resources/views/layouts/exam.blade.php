<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Aksara CBT')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- KaTeX (render LaTeX $...$ dan $$...$$) --}}
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css">
    <script defer
            src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js"></script>
    <script defer
            src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js"
            onload="document.addEventListener('DOMContentLoaded', () => {
                renderMathInElement(document.body, {
                    delimiters: [
                        { left: '$$', right: '$$', display: true  },
                        { left: '$',  right: '$',  display: false }
                    ],
                    throwOnError: false
                });
            })"></script>

    <style>
        /* Pastikan teks soal tidak bisa diseleksi & disalin */
        .no-select { user-select: none; }

        /* Small */
html[data-font-scale="small"] .text-xs { font-size: 0.6875rem !important; }
html[data-font-scale="small"] .text-sm { font-size: 0.8125rem !important; }
html[data-font-scale="small"] .text-base { font-size: 0.9375rem !important; }
html[data-font-scale="small"] .text-lg { font-size: 1.0625rem !important; }
html[data-font-scale="small"] .text-xl { font-size: 1.1875rem !important; }
html[data-font-scale="small"] .text-2xl { font-size: 1.375rem !important; }
html[data-font-scale="small"] .text-3xl { font-size: 1.625rem !important; }
html[data-font-scale="small"] .text-4xl { font-size: 2rem !important; }

/* Large */
html[data-font-scale="large"] .text-xs { font-size: 1rem !important; }
html[data-font-scale="large"] .text-sm { font-size: 1.125rem !important; }
html[data-font-scale="large"] .text-base { font-size: 1.25rem !important; }
html[data-font-scale="large"] .text-lg { font-size: 1.5rem !important; }
html[data-font-scale="large"] .text-xl { font-size: 1.75rem !important; }
html[data-font-scale="large"] .text-2xl { font-size: 2rem !important; }
html[data-font-scale="large"] .text-3xl { font-size: 2.5rem !important; }
html[data-font-scale="large"] .text-4xl { font-size: 3.25rem !important; }

        html[data-theme="dark"] body.exam-interface {
            background: #020617;
            color: #e2e8f0;
        }

        html[data-theme="dark"] body.exam-interface .bg-slate-100 { background-color: #020617 !important; }
        html[data-theme="dark"] body.exam-interface .bg-white { background-color: #0f172a !important; }
        html[data-theme="dark"] body.exam-interface .bg-white\/95 { background-color: rgb(15 23 42 / 0.95) !important; }
        html[data-theme="dark"] body.exam-interface .bg-slate-50 { background-color: #111827 !important; }
        html[data-theme="dark"] body.exam-interface .bg-blue-50 { background-color: #172554 !important; }
        html[data-theme="dark"] body.exam-interface .bg-emerald-50 { background-color: #052e16 !important; }
        html[data-theme="dark"] body.exam-interface .bg-amber-50 { background-color: #451a03 !important; }
        html[data-theme="dark"] body.exam-interface .bg-red-50 { background-color: #450a0a !important; }
        html[data-theme="dark"] body.exam-interface .border-slate-100,
        html[data-theme="dark"] body.exam-interface .border-slate-200,
        html[data-theme="dark"] body.exam-interface .border-slate-300 { border-color: #334155 !important; }
        html[data-theme="dark"] body.exam-interface .border-blue-100,
        html[data-theme="dark"] body.exam-interface .border-blue-200 { border-color: #1d4ed8 !important; }
        html[data-theme="dark"] body.exam-interface .border-emerald-100 { border-color: #047857 !important; }
        html[data-theme="dark"] body.exam-interface .border-amber-300 { border-color: #d97706 !important; }
        html[data-theme="dark"] body.exam-interface .border-red-100 { border-color: #b91c1c !important; }
        html[data-theme="dark"] body.exam-interface .text-slate-950,
        html[data-theme="dark"] body.exam-interface .text-slate-900,
        html[data-theme="dark"] body.exam-interface .text-slate-800,
        html[data-theme="dark"] body.exam-interface .text-slate-700 { color: #f8fafc !important; }
        html[data-theme="dark"] body.exam-interface .text-slate-600,
        html[data-theme="dark"] body.exam-interface .text-slate-500,
        html[data-theme="dark"] body.exam-interface .text-slate-400 { color: #cbd5e1 !important; }
        html[data-theme="dark"] body.exam-interface .text-blue-700,
        html[data-theme="dark"] body.exam-interface .text-blue-800 { color: #93c5fd !important; }
        html[data-theme="dark"] body.exam-interface .text-emerald-700,
        html[data-theme="dark"] body.exam-interface .text-emerald-800 { color: #86efac !important; }
        html[data-theme="dark"] body.exam-interface .text-amber-700 { color: #fcd34d !important; }
        html[data-theme="dark"] body.exam-interface .text-red-600,
        html[data-theme="dark"] body.exam-interface .text-red-700 { color: #fca5a5 !important; }
        html[data-theme="dark"] body.exam-interface .shadow-sm,
        html[data-theme="dark"] body.exam-interface .shadow-md,
        html[data-theme="dark"] body.exam-interface .shadow-2xl { box-shadow: 0 18px 45px rgb(0 0 0 / 0.35) !important; }
        html[data-theme="dark"] body.exam-interface .prose { color: #e2e8f0; }
        html[data-theme="dark"] body.exam-interface .prose :where(strong) { color: #f8fafc; }
        .question-content table { width: max-content; min-width: 100%; border-collapse: collapse; }
        .question-content th, .question-content td { border: 1px solid #cbd5e1; padding: .55rem .75rem; text-align: left; }
        .question-content th { background: #f1f5f9; font-weight: 700; }
        .question-content ul { list-style: disc; padding-left: 1.5rem; }
        .question-content ol { list-style: decimal; padding-left: 1.5rem; }
        .question-content blockquote { border-left: 4px solid #94a3b8; padding-left: 1rem; color: #475569; }
        .question-content a { color: #1d4ed8; text-decoration: underline; }
    </style>

    <script>
        (() => {
            const fontScale = localStorage.getItem('aksara_font_scale') || 'normal';
            const theme = localStorage.getItem('aksara_theme') || 'light';
            document.documentElement.dataset.fontScale = fontScale;
            document.documentElement.dataset.theme = theme;
        })();
    </script>

    @stack('styles')
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased @yield('body_class')">

    @yield('content')

    @stack('scripts')
</body>
</html>
