@php
    $editing = isset($soal);
    $safeQuestionHtml = app(\App\Services\QuestionContent::class)->rich(old('teks_soal', $soal->teks_soal ?? '')) ?? '';
    $itemValues = old('items', $editing
        ? $soal->items->map(fn ($item) => [
            'id' => $item->id,
            'konten' => $item->konten,
            'is_correct' => $item->is_correct ? '1' : '0',
            'correct_value' => $item->correct_value ?: ($item->is_correct ? 'A' : 'B'),
            'gambar' => $item->gambar,
            'gambar_url' => $item->gambar ? route('admin.media.soal', $item->gambar) : null,
        ])->values()->all()
        : []);
    $tableValue = old('tabel_tsv', $editing && $soal->tabel_data
        ? collect($soal->tabel_data)->map(fn ($row) => implode("\t", $row))->implode("\n")
        : '');
@endphp

<div>
    <label class="label">Mata Pelajaran *</label>
    <select name="mata_pelajaran_id" required class="input">
        <option value="">-- Pilih --</option>
        @foreach($mapels->groupBy('tipe') as $tipe => $group)
            <optgroup label="{{ ucfirst($tipe) }}">
                @foreach($group as $mp)
                    <option value="{{ $mp->id }}" @selected((int) old('mata_pelajaran_id', $soal->mata_pelajaran_id ?? $selectedMapelId ?? null) === (int) $mp->id)>
                        {{ $mp->nama }} ({{ $mp->tipe === 'pilihan' ? '10' : '5' }} poin)
                    </option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="label">Nomor Urut *</label>
        <input type="number" name="nomor_urut" min="1" value="{{ old('nomor_urut', $soal->nomor_urut ?? '') }}" class="input" required>
    </div>
    <div>
        <label class="label">Tipe Soal *</label>
        <select name="tipe_soal" id="tipe-soal" class="input" required>
            <option value="pilihan_ganda" @selected(old('tipe_soal', $soal->tipe_soal ?? 'pilihan_ganda') === 'pilihan_ganda')>Pilihan ganda</option>
            <option value="benar_salah" @selected(old('tipe_soal', $soal->tipe_soal ?? '') === 'benar_salah')>Dua pilihan per pernyataan</option>
            <option value="pilih_semua" @selected(old('tipe_soal', $soal->tipe_soal ?? '') === 'pilih_semua')>Pilih semua yang tepat</option>
        </select>
    </div>
</div>

<div>
    <label class="label">Isi Soal <span class="text-xs font-normal text-blue-600">(rich text dan LaTeX didukung)</span></label>
    <div class="rich-toolbar" role="toolbar" aria-label="Format isi soal">
        @foreach([['bold','B'],['italic','I'],['underline','U'],['strikeThrough','S'],['insertUnorderedList','• List'],['insertOrderedList','1. List'],['formatBlock:H2','H2'],['formatBlock:BLOCKQUOTE','Kutipan'],['superscript','x²'],['subscript','x₂'],['createLink','Link'],['insertTable','Tabel']] as [$command,$label])
            <button type="button" class="rich-command" data-command="{{ $command }}">{{ $label }}</button>
        @endforeach
    </div>
    <div id="rich-editor" class="rich-editor" contenteditable="true" data-placeholder="Tulis soal di sini...">{!! $safeQuestionHtml !!}</div>
    <textarea name="teks_soal" id="input-soal" class="hidden">{{ $safeQuestionHtml }}</textarea>
</div>

<section id="binary-label-fields" class="hidden rounded-xl border border-indigo-200 bg-indigo-50/50 p-4">
    <h4 class="mb-3 font-semibold text-gray-900">Label Dua Pilihan</h4>
    <div class="grid gap-3 sm:grid-cols-2">
        <div><label class="label">Label pilihan pertama (A) *</label><input id="option-label-a" name="option_label_a" maxlength="80" class="input" value="{{ old('option_label_a', $soal->option_label_a ?? 'Benar') }}"></div>
        <div><label class="label">Label pilihan kedua (B) *</label><input id="option-label-b" name="option_label_b" maxlength="80" class="input" value="{{ old('option_label_b', $soal->option_label_b ?? 'Salah') }}"></div>
    </div>
</section>

<div>
    <label class="label">Tabel (opsional)</label>
    <textarea name="tabel_tsv" id="input-table" rows="5" class="input font-mono" placeholder="Nama&#9;Nilai&#10;Ani&#9;90">{{ $tableValue }}</textarea>
    <p class="mt-1 text-xs text-gray-500">Tempel langsung dari Excel/Google Sheets. Baris pertama ditampilkan sebagai header.</p>
</div>

<div>
    <label class="label">Gambar Soal (opsional, maksimum 5 MB)</label>
    @if($editing && $soal->gambar_soal)
        <img id="preview-gambar-soal" src="{{ route('admin.media.soal', $soal->gambar_soal) }}" alt="Gambar soal saat ini" class="mb-3 max-h-52 rounded-lg border object-contain">
    @else
        <img id="preview-gambar-soal" alt="Preview gambar soal" class="mb-3 hidden max-h-52 rounded-lg border object-contain">
    @endif
    <input type="file" name="gambar_soal" accept="image/jpeg,image/png,image/webp,image/gif" class="input-file image-preview-input" data-preview="preview-gambar-soal">
</div>

<section id="pg-fields" class="space-y-4 rounded-xl border border-gray-200 p-4">
    <div>
        <label class="label">Tipe Opsi *</label>
        <select name="tipe_opsi" id="sel-tipe-opsi" class="input">
            @foreach(['teks' => 'Teks', 'gambar' => 'Gambar', 'campuran' => 'Campuran'] as $value => $label)
                <option value="{{ $value }}" @selected(old('tipe_opsi', $soal->tipe_opsi ?? 'teks') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    @foreach(['A','B','C','D','E'] as $h)
        @php $key = strtolower($h); @endphp
        <div class="rounded-xl border border-gray-200 p-4">
            <p class="mb-2 font-semibold text-blue-700">Opsi {{ $h }}</p>
            <div class="opsi-teks">
                <input type="text" name="opsi_{{ $key }}" id="input-opsi-{{ $h }}" value="{{ old('opsi_'.$key, $soal->{'opsi_'.$key} ?? '') }}" class="input" placeholder="Teks opsi {{ $h }}">
            </div>
            <div class="opsi-gambar mt-2 hidden">
                @if($editing && $soal->{'gambar_opsi_'.$key})
                    <img id="preview-gambar-opsi-{{ $h }}" src="{{ route('admin.media.soal', $soal->{'gambar_opsi_'.$key}) }}" alt="Gambar opsi {{ $h }}" class="mb-2 max-h-40 rounded-lg border object-contain">
                @else
                    <img id="preview-gambar-opsi-{{ $h }}" alt="Preview gambar opsi {{ $h }}" class="mb-2 hidden max-h-40 rounded-lg border object-contain">
                @endif
                <input type="file" name="gambar_opsi_{{ $key }}" accept="image/jpeg,image/png,image/webp,image/gif" class="input-file image-preview-input" data-preview="preview-gambar-opsi-{{ $h }}">
            </div>
        </div>
    @endforeach

    <div>
        <label class="label">Kunci Jawaban *</label>
        <select name="kunci_jawaban" id="kunci-pg" class="input">
            @foreach(['A','B','C','D','E'] as $h)
                <option value="{{ $h }}" @selected(old('kunci_jawaban', $soal->kunci_jawaban ?? 'A') === $h)>{{ $h }}</option>
            @endforeach
        </select>
    </div>
</section>

<section id="complex-fields" class="hidden space-y-3 rounded-xl border border-blue-200 bg-blue-50/40 p-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h4 class="font-semibold text-gray-900">Daftar Pernyataan</h4>
            <p class="text-xs text-gray-500">Urutan dapat diubah dengan tombol naik/turun.</p>
        </div>
        <button type="button" id="add-item" class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white">+ Pernyataan</button>
    </div>
    <div id="item-editor" class="space-y-3"></div>
</section>

<script type="application/json" id="initial-question-items">@json($itemValues)</script>
