<section id="subject-view" class="min-h-screen bg-slate-100">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto grid max-w-7xl gap-4 px-4 py-5 sm:px-6 lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] lg:items-start lg:px-8">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-widest text-blue-700">Aksara CBT</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">Pilih Mata Pelajaran</h1>
                <p id="subject-participant" class="mt-1 text-sm text-slate-500">Memuat peserta...</p>
            </div>

            <div class="justify-self-stretch lg:justify-self-center">
                @include('exam.partials.header-timer')
            </div>

            <div class="w-full lg:w-[360px] lg:justify-self-end">
                @include('exam.partials.display-settings')
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div id="subject-loading" class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">
            <p class="text-sm font-medium text-slate-700">Memuat data ujian...</p>
            <p class="mt-1 text-sm text-slate-400">Sebentar ya, sistem sedang mengambil soal.</p>
        </div>

        <div id="subject-content" class="hidden space-y-10">
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-950">Ringkasan Pengerjaan</p>
                        <p class="mt-1 text-sm text-slate-500">
                            Total terjawab:
                            <span id="subject-total-answered" class="font-semibold text-slate-900">0</span>/<span id="subject-total-questions">0</span>
                            soal.
                            Kirim hanya jika semua mapel sudah selesai.
                        </p>
                    </div>

                    <button type="button"
                            onclick="openSubmitDialog()"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 sm:w-auto">
                        Buka Ringkasan dan Kirim
                    </button>
                </div>
            </section>

            <section>
                <div class="mb-4 flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">Mata Pelajaran Wajib</h2>
                        <p class="text-sm text-slate-500">Mapel yang dikerjakan semua peserta.</p>
                    </div>
                </div>
                <div id="mandatory-subjects" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"></div>
            </section>

            <section>
                <div class="mb-4 flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">Mata Pelajaran Pilihan</h2>
                        <p class="text-sm text-slate-500">Mapel pilihan sesuai data peserta.</p>
                    </div>
                </div>
                <div id="elective-subjects" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"></div>
            </section>
        </div>
    </main>
</section>
