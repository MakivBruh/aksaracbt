<div id="modal-peringatan" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4">
    <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-xl font-bold text-red-600">!</div>
        <h2 class="mt-4 text-xl font-semibold text-red-600">Peringatan</h2>
        <p id="modal-pesan" class="mt-2 text-sm text-slate-700">Aktivitas mencurigakan terdeteksi.</p>
        <p id="modal-hitung" class="mt-2 text-sm font-semibold text-red-500"></p>
        <button type="button" onclick="tutupModal(); mintaFullscreen();" class="mt-5 w-full rounded-xl bg-blue-700 py-3 text-sm font-semibold text-white hover:bg-blue-800">
            Kembali ke Ujian
        </button>
    </div>
</div>

<div id="exam-mode-gate" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
    <div class="w-full max-w-md rounded-3xl bg-white p-6 text-center shadow-2xl">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-2xl font-bold text-blue-700">!</div>
        <h2 class="mt-4 text-xl font-semibold text-slate-950">Masuk Mode Ujian</h2>
        <p id="exam-mode-gate-message" class="mt-2 text-sm leading-6 text-slate-600">
            Sebelum memilih mata pelajaran, aktifkan mode layar penuh. Sistem juga akan mencoba menjaga layar tetap menyala selama ujian.
        </p>
        <button type="button"
                onclick="enterExamMode()"
                class="mt-5 w-full rounded-xl bg-blue-700 py-3 text-sm font-semibold text-white hover:bg-blue-800">
            Masuk Mode Ujian
        </button>
        <p class="mt-3 text-xs text-slate-400">
            Gunakan satu perangkat. Jangan berpindah aplikasi, membuka pop-up, menyalin teks, atau menekan tombol navigasi sistem selama ujian.
        </p>
    </div>
</div>
