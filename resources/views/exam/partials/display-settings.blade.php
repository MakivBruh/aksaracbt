<div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-slate-950">Tampilan</p>
            <p class="text-xs text-slate-500">Sesuaikan kenyamanan membaca.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="flex rounded-xl border border-slate-200 bg-slate-50 p-1" aria-label="Ukuran huruf">
                <button type="button"
                        data-font-button="small"
                        onclick="setFontScale('small')"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-white">
                    A-
                </button>
                <button type="button"
                        data-font-button="normal"
                        onclick="setFontScale('normal')"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-white">
                    A
                </button>
                <button type="button"
                        data-font-button="large"
                        onclick="setFontScale('large')"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-white">
                    A+
                </button>
            </div>

            <button type="button"
                    onclick="toggleTheme()"
                    data-theme-toggle
                    class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-white">
                Mode Gelap
            </button>
        </div>
    </div>
</div>
