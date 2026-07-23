<div id="image-zoom-modal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-slate-950/95 p-3 sm:p-6" role="dialog" aria-modal="true" aria-label="Perbesar gambar soal">
    <button type="button" onclick="closeImageZoom()" class="absolute right-3 top-3 z-10 flex h-11 w-11 items-center justify-center rounded-full bg-white text-2xl font-bold text-slate-900 shadow-lg" aria-label="Tutup gambar">&times;</button>

    <div class="absolute bottom-4 left-1/2 z-10 flex -translate-x-1/2 items-center gap-2 rounded-full bg-white/95 p-2 shadow-xl">
        <button type="button" onclick="adjustImageZoom(-0.25)" class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-xl font-bold text-slate-800" aria-label="Perkecil">−</button>
        <span id="image-zoom-level" class="w-14 text-center text-xs font-semibold text-slate-700">100%</span>
        <button type="button" onclick="adjustImageZoom(0.25)" class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-700 text-xl font-bold text-white" aria-label="Perbesar">+</button>
    </div>

    <div class="flex h-full w-full items-center justify-center overflow-auto" onclick="if (event.target === this) closeImageZoom()">
        <img id="image-zoom-preview" alt="Gambar soal diperbesar" class="max-h-[82vh] max-w-[94vw] origin-center object-contain transition-transform duration-150" draggable="false">
    </div>
</div>
