<div id="submit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4">
    <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
        <h2 class="text-xl font-semibold text-slate-950">Kirim Seluruh Ujian?</h2>
        <p class="mt-2 text-sm text-slate-500">Tombol ini mengakhiri semua mata pelajaran, bukan hanya mapel yang sedang dibuka.</p>

        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-950">Mapel yang sedang dibuka</p>
                    <p id="submit-current-subject" class="text-sm text-slate-500">-</p>
                </div>
                <p class="text-sm font-semibold text-slate-700">
                    <span id="submit-subject-answered">0</span>/<span id="submit-subject-total">0</span> terjawab
                </p>
            </div>
        </div>

        <p class="mt-5 text-sm font-semibold text-slate-950">Total seluruh ujian</p>
        <div class="mt-3 grid grid-cols-3 gap-3 text-center text-sm">
            <div class="rounded-xl bg-emerald-50 p-4">
                <p class="text-emerald-700">Terjawab</p>
                <p id="submit-answered" class="mt-1 text-2xl font-semibold text-emerald-700">0</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4">
                <p class="text-slate-500">Belum</p>
                <p id="submit-unanswered" class="mt-1 text-2xl font-semibold text-slate-950">0</p>
            </div>
            <div class="rounded-xl bg-amber-50 p-4">
                <p class="text-amber-700">Ragu-ragu</p>
                <p id="submit-review" class="mt-1 text-2xl font-semibold text-amber-700">0</p>
            </div>
        </div>
        <p class="mt-4 rounded-xl bg-red-50 p-3 text-sm font-medium text-red-700">
            Setelah dikirim, peserta tidak bisa kembali mengerjakan soal.
        </p>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" onclick="closeSubmitDialog()" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Batal
            </button>
            <button type="button" onclick="submitUjian(false)" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                Ya, Kirim Seluruh Ujian
            </button>
        </div>
    </div>
</div>
