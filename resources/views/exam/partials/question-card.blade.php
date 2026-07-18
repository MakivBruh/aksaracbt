<article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
    <div class="mb-4 flex flex-col gap-3 border-b border-slate-100 pb-4 sm:mb-5 sm:flex-row sm:items-start sm:justify-between sm:pb-5">
        <div class="min-w-0">
            <p id="question-eyebrow" class="text-xs font-semibold uppercase tracking-widest text-blue-700">Soal</p>
            <h2 id="question-heading" class="mt-1 text-lg font-semibold text-slate-950 sm:text-xl">Memuat soal...</h2>
        </div>
            <button id="review-button"
                type="button"
                onclick="toggleReview()"
                class="min-h-11 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700 sm:min-h-0">
            Tandai Ragu-ragu
        </button>
    </div>

    <div id="question-content" class="prose prose-slate max-w-none text-base leading-7 text-slate-800"></div>
    <div id="question-image-wrap" class="mt-4 hidden sm:mt-5">
        <img id="question-image" src="" alt="Gambar soal" class="max-h-[58svh] w-auto max-w-full rounded-xl border border-slate-200 object-contain sm:max-h-[420px]">
    </div>

    <div id="option-list" class="mt-5 space-y-3 sm:mt-6"></div>

    <div class="fixed inset-x-0 bottom-0 z-20 flex items-center justify-between gap-3 border-t border-slate-200 bg-white/95 px-3 py-3 shadow-[0_-14px_30px_rgba(15,23,42,0.08)] backdrop-blur sm:static sm:mt-8 sm:border-slate-100 sm:bg-transparent sm:px-0 sm:pt-5 sm:shadow-none sm:backdrop-blur-0">
            <button id="prev-button"
                type="button"
                onclick="goPrevQuestion()"
                class="min-h-12 flex-1 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 sm:min-h-0 sm:flex-none sm:px-5">
            Sebelumnya
        </button>
        <button id="next-button"
                type="button"
                onclick="goNextQuestion()"
                class="min-h-12 flex-1 rounded-xl bg-blue-700 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 sm:min-h-0 sm:flex-none sm:px-5">
            Berikutnya
        </button>
    </div>
</article>
