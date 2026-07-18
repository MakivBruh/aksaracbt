@extends('layouts.admin')

@section('title', 'Tambah Soal Baru')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

    {{-- ─── FORM INPUT ─────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-5">Detail Soal</h3>

        <form method="POST"
              action="{{ route('admin.soal.store') }}"
              enctype="multipart/form-data"
              class="space-y-5">
            @csrf

            {{-- Mata pelajaran --}}
            <div>
                <label class="label">Mata Pelajaran *</label>
                <select name="mata_pelajaran_id" required class="input">
                    <option value="">-- Pilih --</option>
                    @foreach($mapels->groupBy('tipe') as $tipe => $group)
                        <optgroup label="{{ ucfirst($tipe) }}">
                            @foreach($group as $mp)
                                <option value="{{ $mp->id }}" @selected((int) old('mata_pelajaran_id', $selectedMapelId ?? null) === (int) $mp->id)>
                                    {{ $mp->nama }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            {{-- Nomor urut --}}
            <div>
                <label class="label">Nomor Urut *</label>
                <input type="number" name="nomor_urut" min="1" value="{{ old('nomor_urut') }}"
                       class="input" required>
            </div>

            {{-- Teks soal (LaTeX supported) --}}
            <div>
                <label class="label">
                    Teks Soal
                    <span class="text-xs text-blue-600 font-normal">(LaTeX: $...$ inline, $$...$$ block)</span>
                </label>
                <textarea name="teks_soal"
                          id="input-soal"
                          rows="4"
                          class="input font-mono text-sm"
                          placeholder="Contoh: Nilai dari $x^2 - 4 = 0$ adalah...">{{ old('teks_soal') }}</textarea>
            </div>

            {{-- Gambar soal --}}
            <div>
                <label class="label">Gambar Soal (opsional, max 5MB)</label>
                <input type="file" name="gambar_soal" accept="image/*" class="input-file">
                <p class="text-xs text-gray-400 mt-1">Otomatis dikompresi ke max 800px & 80% kualitas.</p>
            </div>

            {{-- Tipe opsi --}}
            <div>
                <label class="label">Tipe Opsi *</label>
                <select name="tipe_opsi" id="sel-tipe-opsi" class="input" required onchange="toggleOpsiMode(this.value)">
                    <option value="teks"     @selected(old('tipe_opsi','teks')=='teks')>Teks (+ LaTeX)</option>
                    <option value="gambar"   @selected(old('tipe_opsi')=='gambar')>Gambar</option>
                    <option value="campuran" @selected(old('tipe_opsi')=='campuran')>Campuran (Teks + Gambar)</option>
                </select>
            </div>

            {{-- Opsi A–E --}}
            @foreach(['A','B','C','D','E'] as $h)
            <div class="border rounded-xl p-4 space-y-2">
                <p class="font-semibold text-blue-700">Opsi {{ $h }}</p>

                <div id="wrap-teks-{{ $h }}" class="opsi-teks">
                    <label class="label text-xs">Teks (boleh LaTeX)</label>
                    <input type="text"
                           name="opsi_{{ strtolower($h) }}"
                           id="input-opsi-{{ $h }}"
                           value="{{ old('opsi_' . strtolower($h)) }}"
                           class="input font-mono text-sm"
                           placeholder="Teks opsi {{ $h }}..."
                           oninput="previewOpsi('{{ $h }}')">
                </div>

                <div id="wrap-gambar-{{ $h }}" class="opsi-gambar hidden">
                    <label class="label text-xs">Gambar Opsi (max 3MB)</label>
                    <input type="file" name="gambar_opsi_{{ strtolower($h) }}" accept="image/*" class="input-file">
                </div>
            </div>
            @endforeach

            {{-- Kunci jawaban --}}
            <div>
                <label class="label">Kunci Jawaban *</label>
                <select name="kunci_jawaban" required class="input">
                    @foreach(['A','B','C','D','E'] as $h)
                        <option value="{{ $h }}" @selected(old('kunci_jawaban') == $h)>{{ $h }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl">
                    Simpan Soal
                </button>
                <a href="{{ route('admin.soal.index') }}"
                   class="px-5 py-3 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 text-center">
                    Batal
                </a>
            </div>
        </form>
    </div>

    {{-- ─── LIVE PREVIEW ───────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow-sm p-6 xl:sticky xl:top-6 xl:self-start">
        <h3 class="font-semibold text-gray-800 mb-4">Preview Live</h3>

        {{-- Preview soal --}}
        <div class="border rounded-xl p-5 mb-5 min-h-20">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-2">Teks Soal</p>
            <div id="preview-soal" class="text-gray-800 leading-relaxed text-sm">
                <span class="text-gray-300 italic">Mulai ketik soal di sebelah kiri...</span>
            </div>
        </div>

        {{-- Preview opsi --}}
        <div class="space-y-2">
            @foreach(['A','B','C','D','E'] as $h)
            <div id="preview-opsi-wrap-{{ $h }}" class="hidden flex items-start gap-2 text-sm border rounded-lg p-3">
                <span class="font-bold text-blue-700 w-5 flex-shrink-0">{{ $h }}.</span>
                <div id="preview-opsi-{{ $h }}" class="flex-1 text-gray-700"></div>
            </div>
            @endforeach
        </div>

        <p class="text-xs text-gray-400 mt-4">
            * Preview diperbarui otomatis saat kamu mengetik.
        </p>
    </div>
</div>
@endsection

@push('styles')
<style type="text/tailwindcss">
.label { @apply block text-sm font-medium text-gray-700 mb-1; }
.input { @apply w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500; }
.input-file { @apply w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100; }
</style>
@endpush

@push('scripts')
<script>
// ── Preview LaTeX live ─────────────────────────────────────────────
function renderKatex(el, src) {
    el.innerHTML = src || '';
    if (window._katexReady && window.renderMathInElement) {
        renderMathInElement(el, {
            delimiters: [
                { left: '$$', right: '$$', display: true  },
                { left: '$',  right: '$',  display: false },
            ],
            throwOnError: false,
        });
    }
}

// Preview teks soal
const inputSoal    = document.getElementById('input-soal');
const previewSoal  = document.getElementById('preview-soal');
let soalDebounce;
inputSoal.addEventListener('input', () => {
    clearTimeout(soalDebounce);
    soalDebounce = setTimeout(() => renderKatex(previewSoal, inputSoal.value), 300);
});

// Preview opsi
function previewOpsi(huruf) {
    const val  = document.getElementById(`input-opsi-${huruf}`).value;
    const wrap = document.getElementById(`preview-opsi-wrap-${huruf}`);
    const prev = document.getElementById(`preview-opsi-${huruf}`);
    wrap.classList.toggle('hidden', !val.trim());
    if (val.trim()) renderKatex(prev, val);
}

// Tampil/sembunyikan field berdasarkan tipe opsi
function toggleOpsiMode(mode) {
    const showTeks   = mode === 'teks'   || mode === 'campuran';
    const showGambar = mode === 'gambar' || mode === 'campuran';

    document.querySelectorAll('.opsi-teks').forEach(el =>
        el.classList.toggle('hidden', !showTeks));
    document.querySelectorAll('.opsi-gambar').forEach(el =>
        el.classList.toggle('hidden', !showGambar));
}

// Inisialisasi mode default
toggleOpsiMode('teks');
</script>
@endpush
