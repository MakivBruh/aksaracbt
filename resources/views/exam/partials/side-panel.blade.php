<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
    <div class="mb-3 flex items-center justify-between">
        <h3 class="font-semibold text-slate-950">Kemajuan Mapel Ini</h3>
        <span id="progress-percent" class="text-sm font-semibold text-blue-700">0%</span>
    </div>
    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
        <div id="progress-bar" class="h-full rounded-full bg-blue-700 transition-all" style="width: 0%"></div>
    </div>
    <dl class="mt-4 grid grid-cols-3 gap-2 text-center text-xs">
        <div class="rounded-xl bg-slate-50 p-2.5 sm:p-3">
            <dt class="text-slate-500">Terjawab</dt>
            <dd id="progress-answered" class="mt-1 text-lg font-semibold text-slate-950">0</dd>
        </div>
        <div class="rounded-xl bg-slate-50 p-2.5 sm:p-3">
            <dt class="text-slate-500">Belum</dt>
            <dd id="progress-remaining" class="mt-1 text-lg font-semibold text-slate-950">0</dd>
        </div>
        <div class="rounded-xl bg-amber-50 p-2.5 sm:p-3">
            <dt class="text-amber-700">Ragu</dt>
            <dd id="progress-review" class="mt-1 text-lg font-semibold text-amber-700">0</dd>
        </div>
    </dl>
</div>

<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
    <h3 class="font-semibold text-slate-950">Nomor Soal</h3>
    <div id="question-navigator" class="mt-4 grid grid-cols-5 gap-2 sm:grid-cols-6 lg:grid-cols-5"></div>
    <div class="mt-5 grid grid-cols-2 gap-2 text-xs text-slate-600">
        <span class="flex items-center gap-2"><i class="h-3 w-3 rounded bg-blue-700"></i> Aktif</span>
        <span class="flex items-center gap-2"><i class="h-3 w-3 rounded bg-emerald-500"></i> Terjawab</span>
        <span class="flex items-center gap-2"><i class="h-3 w-3 rounded bg-white border border-slate-300"></i> Belum</span>
        <span class="flex items-center gap-2"><i class="h-3 w-3 rounded bg-amber-400"></i> Ragu</span>
    </div>
</div>

<div class="rounded-2xl border border-red-100 bg-red-50 p-4">
    <p class="text-sm font-semibold text-red-700">Kirim Seluruh Ujian</p>
    <p class="mt-1 text-xs leading-5 text-red-600">Gunakan hanya jika semua mata pelajaran sudah selesai diperiksa.</p>
    <button type="button"
            onclick="openSubmitDialog()"
            class="mt-3 w-full rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
        Buka Ringkasan dan Kirim
    </button>
</div>
