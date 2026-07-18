@extends('layouts.admin')

@section('title', 'Edit Soal #' . $soal->nomor_urut)

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

    {{-- ─── FORM INPUT ─────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-5">Edit Soal</h3>

        <form method="POST"
              action="{{ route('admin.soal.update', $soal) }}"
              enctype="multipart/form-data"
              class="space-y-5">
            @csrf
            @method('PUT')

            {{-- Mata pelajaran --}}
            <div>
                <label class="label">Mata Pelajaran *</label>
                <select name="mata_pelajaran_id" required class="input">
                    @foreach($mapels->groupBy('tipe') as $tipe => $group)
                        <optgroup label="{{ ucfirst($tipe) }}">
                            @foreach($group as $mp)
                                <option value="{{ $mp->id }}"
                                    @selected(old('mata_pelajaran_id', $soal->mata_pelajaran_id) == $mp->id)>
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
                <input type="number" name="nomor_urut" min="1"
                       value="{{ old('nomor_urut', $soal->nomor_urut) }}" class="input" required>
            </div>

            {{-- Teks soal --}}
            <div>
                <label class="label">
                    Teks Soal
                    <span class="text-xs text-blue-600 font-normal">(LaTeX: $...$ inline, $$...$$ block)</span>
                </label>
                <textarea name="teks_soal" id="input-soal" rows="4"
                          class="input font-mono text-sm">{{ old('teks_soal', $soal->teks_soal) }}</textarea>
            </div>

            {{-- Gambar soal --}}
            <div>
                <label class="label">Gambar Soal (opsional, max 5MB)</label>
                @if($soal->gambar_soal)
                    <div class="mb-2 flex items-center gap-2 text-xs text-gray-500">
                        <img src="{{ Storage::disk('soal_images')->temporaryUrl('soal-images/' . $soal->gambar_soal, now()->addMinutes(10)) }}"
                             alt="Gambar soal saat ini" class="h-16 rounded border" loading="lazy">
                        Gambar saat ini — upload file baru untuk mengganti.
                    </div>
                @endif
                <input type="file" name="gambar_soal" accept="image/*" class="input-file">
            </div>

            {{-- Tipe opsi --}}
            <div>
                <label class="label">Tipe Opsi *</label>
                <select name="tipe_opsi" id="sel-tipe-opsi" class="input" required onchange="toggleOpsiMode(this.value)">
                    <option value="teks"     @selected(old('tipe_opsi', $soal->tipe_opsi)=='teks')>Teks (+ LaTeX)</option>
                    <option value="gambar"   @selected(old('tipe_opsi', $soal->tipe_opsi)=='gambar')>Gambar</option>
                    <option value="campuran" @selected(old('tipe_opsi', $soal->tipe_opsi)=='campuran')>Campuran (Teks + Gambar)</option>
                </select>
            </div>

            {{-- Opsi A–E --}}
            @foreach(['A','B','C','D','E'] as $h)
            @php $kolom = strtolower($h); @endphp
            <div class="border rounded-xl p-4 space-y-2">
                <p class="font-semibold text-blue-700">Opsi {{ $h }}</p>

                <div id="wrap-teks-{{ $h }}" class="opsi-teks">
                    <label class="label text-xs">Teks (boleh LaTeX)</label>
                    <input type="text"
                           name="opsi_{{ $kolom }}"
                           id="input-opsi-{{ $h }}"
                           value="{{ old('opsi_' . $kolom, $soal->{'opsi_' . $kolom}) }}"
                           class="input font-mono text-sm"
                           oninput="previewOpsi('{{ $h }}')">
                </div>

                <div id="wrap-gambar-{{ $h }}" class="opsi-gambar hidden">
                    <label class="label text-xs">Gambar Opsi (max 3MB)</label>
                    @if($soal->{'gambar_opsi_' . $kolom})
                        <p class="text-xs text-gray-400 mb-1">Sudah ada gambar — upload baru untuk mengganti.</p>
                    @endif
                    <input type="file" name="gambar_opsi_{{ $kolom }}" accept="image/*" class="input-file">
                </div>
            </div>
            @endforeach

            {{-- Kunci jawaban --}}
            <div>
                <label class="label">Kunci Jawaban *</label>
                <select name="kunci_jawaban" required class="input">
                    @foreach(['A','B','C','D','E'] as $h)
                        <option value="{{ $h }}" @selected(old('kunci_jawaban', $soal->kunci_jawaban) == $h)>{{ $h }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl">
                    Simpan Perubahan
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

        <div class="border rounded-xl p-5 mb-5 min-h-20">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-2">Teks Soal</p>
            <div id="preview-soal" class="text-gray-800 leading-relaxed text-sm"></div>
        </div>

        <div class="space-y-2">
            @foreach(['A','B','C','D','E'] as $h)
            <div id="preview-opsi-wrap-{{ $h }}" class="flex items-start gap-2 text-sm border rounded-lg p-3">
                <span class="font-bold text-blue-700 w-5 flex-shrink-0">{{ $h }}.</span>
                <div id="preview-opsi-{{ $h }}" class="flex-1 text-gray-700"></div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.label {
    display: block;
    margin-bottom: .25rem;
    color: #374151;
    font-size: .875rem;
    font-weight: 500;
}
.input {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: .5rem;
    padding: .5rem .75rem;
    color: #111827;
    background: #fff;
    font-size: .875rem;
    outline: none;
}
.input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgb(59 130 246 / .2);
}
.input-file {
    width: 100%;
    color: #6b7280;
    font-size: .875rem;
}
.input-file::file-selector-button {
    margin-right: .75rem;
    border: 0;
    border-radius: .5rem;
    background: #eff6ff;
    color: #1d4ed8;
    padding: .375rem .75rem;
    font-weight: 600;
}
.input-file:hover::file-selector-button {
    background: #dbeafe;
}
</style>
@endpush

@push('scripts')
<script>
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

const inputSoal   = document.getElementById('input-soal');
const previewSoal = document.getElementById('preview-soal');
let soalDebounce;
inputSoal.addEventListener('input', () => {
    clearTimeout(soalDebounce);
    soalDebounce = setTimeout(() => renderKatex(previewSoal, inputSoal.value), 300);
});
renderKatex(previewSoal, inputSoal.value); // render awal saat halaman dibuka

function previewOpsi(huruf) {
    const val  = document.getElementById(`input-opsi-${huruf}`).value;
    const prev = document.getElementById(`preview-opsi-${huruf}`);
    renderKatex(prev, val);
}
['A','B','C','D','E'].forEach(previewOpsi); // render awal semua opsi

function toggleOpsiMode(mode) {
    const showTeks   = mode === 'teks'   || mode === 'campuran';
    const showGambar = mode === 'gambar' || mode === 'campuran';
    document.querySelectorAll('.opsi-teks').forEach(el => el.classList.toggle('hidden', !showTeks));
    document.querySelectorAll('.opsi-gambar').forEach(el => el.classList.toggle('hidden', !showGambar));
}
toggleOpsiMode(document.getElementById('sel-tipe-opsi').value);
</script>
@endpush
