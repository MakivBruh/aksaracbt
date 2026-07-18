<section id="exam-view" class="hidden min-h-screen bg-slate-100 pb-24 no-select lg:pb-0">
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto grid max-w-7xl grid-cols-[minmax(0,1fr)_auto_auto] items-center gap-2 px-3 py-2.5 sm:gap-3 sm:px-6 sm:py-3 lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] lg:px-8">
            <div class="min-w-0">
                <button type="button" onclick="showSubjectSelection()" class="text-[11px] font-semibold uppercase tracking-widest text-blue-700 hover:text-blue-800 sm:text-xs">
                    Mapel
                </button>
                <h1 id="exam-title" class="truncate text-base font-semibold text-slate-950 sm:text-lg">Ujian</h1>
                <p id="exam-participant" class="hidden truncate text-sm text-slate-500 sm:block">Memuat peserta...</p>
            </div>
            <div class="justify-self-center lg:justify-self-center">
                @include('exam.partials.header-timer')
            </div>
            <button type="button"
                    onclick="openMobileNav()"
                    class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm lg:hidden">
                Nomor
            </button>
            <div class="hidden lg:block lg:w-[360px] lg:justify-self-end">
                @include('exam.partials.display-settings')
            </div>
        </div>
    </header>

    <main class="mx-auto grid max-w-7xl gap-4 px-3 py-4 sm:gap-6 sm:px-6 sm:py-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:px-8">
        <div class="space-y-5">
            @include('exam.partials.question-card')
        </div>

        <aside class="hidden lg:block">
            <div class="sticky top-24 space-y-4">
                @include('exam.partials.side-panel')
            </div>
        </aside>
    </main>

    <div id="mobile-nav-backdrop" class="fixed inset-0 z-40 hidden bg-slate-950/50 backdrop-blur-sm lg:hidden" onclick="closeMobileNav()"></div>
    <aside id="mobile-nav" class="fixed inset-x-0 bottom-0 z-50 hidden max-h-[88svh] overflow-y-auto rounded-t-3xl bg-white p-4 shadow-2xl lg:hidden">
        <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-slate-200"></div>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-950">Navigasi Ujian</h2>
            <button type="button" onclick="closeMobileNav()" class="min-h-10 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600">
                Tutup
            </button>
        </div>
        <div class="mb-4 sm:hidden">
            @include('exam.partials.header-timer')
        </div>
        <div class="mb-4">
            @include('exam.partials.display-settings')
        </div>
        @include('exam.partials.side-panel')
    </aside>
</section>
