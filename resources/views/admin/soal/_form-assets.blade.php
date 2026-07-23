@if($onlyStyles ?? false)
<style>
.label{display:block;margin-bottom:.25rem;color:#374151;font-size:.875rem;font-weight:600}.input{width:100%;border:1px solid #d1d5db;border-radius:.5rem;padding:.55rem .75rem;color:#111827;background:#fff;font-size:.875rem;outline:none}.input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgb(59 130 246/.2)}.input-file{width:100%;color:#6b7280;font-size:.875rem}.input-file::file-selector-button{margin-right:.75rem;border:0;border-radius:.5rem;background:#eff6ff;color:#1d4ed8;padding:.4rem .75rem;font-weight:600}.question-table-wrap{max-width:100%;overflow-x:auto}.question-table{width:100%;border-collapse:collapse;font-size:.875rem}.question-table th,.question-table td{min-width:7rem;border:1px solid #cbd5e1;padding:.6rem .75rem;text-align:left}.question-table th{background:#f1f5f9;font-weight:700}.rich-toolbar{display:flex;flex-wrap:wrap;gap:.35rem;border:1px solid #d1d5db;border-bottom:0;border-radius:.5rem .5rem 0 0;background:#f8fafc;padding:.5rem}.rich-command{border:1px solid #cbd5e1;border-radius:.35rem;background:#fff;padding:.3rem .55rem;font-size:.75rem;font-weight:600}.rich-editor{min-height:12rem;border:1px solid #d1d5db;border-radius:0 0 .5rem .5rem;background:#fff;padding:.75rem;outline:none}.rich-editor:empty:before{content:attr(data-placeholder);color:#9ca3af}.question-content{line-height:1.7}.question-content table{display:table;width:max-content;min-width:100%;border-collapse:collapse}.question-content th,.question-content td{border:1px solid #cbd5e1;padding:.5rem .7rem}.question-content ul{list-style:disc;padding-left:1.5rem}.question-content ol{list-style:decimal;padding-left:1.5rem}.question-content blockquote{border-left:4px solid #94a3b8;padding-left:1rem;color:#475569}
</style>
@else
<script>
const typeSelect = document.getElementById('tipe-soal');
const pgFields = document.getElementById('pg-fields');
const complexFields = document.getElementById('complex-fields');
const binaryLabelFields = document.getElementById('binary-label-fields');
const itemEditor = document.getElementById('item-editor');
let itemRows = JSON.parse(document.getElementById('initial-question-items').textContent || '[]');

function escapeText(value) {
    const div = document.createElement('div');
    div.textContent = value || '';
    return div.innerHTML;
}

function safePreviewHtml(value) {
    const doc = new DOMParser().parseFromString(`<div>${value || ''}</div>`, 'text/html');
    doc.querySelectorAll('script,style,iframe,object,embed').forEach(node => node.remove());
    doc.querySelectorAll('*').forEach(node => [...node.attributes].forEach(attr => {
        if (attr.name.toLowerCase().startsWith('on')) node.removeAttribute(attr.name);
    }));
    return doc.body.firstElementChild?.innerHTML || '';
}

function renderQuestionType() {
    const complex = typeSelect.value !== 'pilihan_ganda';
    pgFields.classList.toggle('hidden', complex);
    complexFields.classList.toggle('hidden', !complex);
    binaryLabelFields.classList.toggle('hidden', typeSelect.value !== 'benar_salah');
    document.getElementById('option-label-a').required = typeSelect.value === 'benar_salah';
    document.getElementById('option-label-b').required = typeSelect.value === 'benar_salah';
    document.getElementById('kunci-pg').required = !complex;
    if (complex && itemRows.length === 0) itemRows = [{konten:'', is_correct:'1'}];
    renderItemEditor();
    renderPreview();
}

function renderItemEditor() {
    const binaryLabelA = escapeText(document.getElementById('option-label-a').value.trim() || 'Pilihan pertama');
    const binaryLabelB = escapeText(document.getElementById('option-label-b').value.trim() || 'Pilihan kedua');
    const keyLabelA = typeSelect.value === 'benar_salah'
        ? `${binaryLabelA} (pilihan pertama)`
        : 'Tepat — harus dicentang peserta';
    const keyLabelB = typeSelect.value === 'benar_salah'
        ? `${binaryLabelB} (pilihan kedua)`
        : 'Tidak tepat — jangan dicentang peserta';
    itemEditor.innerHTML = itemRows.map((item, index) => `
        <div class="item-row rounded-xl border border-gray-200 bg-white p-3" data-index="${index}">
            <input type="hidden" name="items[${index}][id]" value="${item.id || ''}">
            <div class="flex gap-2">
                <span class="mt-2 font-bold text-blue-700">${index + 1}.</span>
                <textarea name="items[${index}][konten]" rows="2" required class="input item-content" placeholder="Isi pernyataan">${escapeText(item.konten)}</textarea>
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <label class="text-xs font-semibold text-gray-600">Kunci:</label>
                <select name="items[${index}][correct_value]" class="input item-binary-key" style="width:auto">
                    <option value="A" ${String(item.correct_value || (String(item.is_correct) === '1' ? 'A' : 'B')) === 'A' ? 'selected' : ''}>${keyLabelA}</option>
                    <option value="B" ${String(item.correct_value || (String(item.is_correct) === '1' ? 'A' : 'B')) === 'B' ? 'selected' : ''}>${keyLabelB}</option>
                </select>
                <input type="hidden" name="items[${index}][is_correct]" class="item-key" value="${typeSelect.value === 'benar_salah' ? (String(item.correct_value || (String(item.is_correct) === '1' ? 'A' : 'B')) === 'A' ? '1' : '0') : (String(item.is_correct) === '1' ? '1' : '0')}">
                <button type="button" class="move-up rounded border px-2 py-1 text-xs" ${index === 0 ? 'disabled' : ''}>Naik</button>
                <button type="button" class="move-down rounded border px-2 py-1 text-xs" ${index === itemRows.length - 1 ? 'disabled' : ''}>Turun</button>
                <button type="button" class="remove-item rounded border border-red-200 px-2 py-1 text-xs text-red-600">Hapus</button>
            </div>
            <div class="mt-3">
                ${item.gambar_url ? `<img src="${escapeText(item.gambar_url)}" alt="Gambar pernyataan" class="item-image-preview mb-2 max-h-40 rounded-lg border object-contain">` : `<img alt="Preview gambar pernyataan" class="item-image-preview mb-2 hidden max-h-40 rounded-lg border object-contain">`}
                <input type="file" name="items[${index}][gambar]" accept="image/jpeg,image/png,image/webp,image/gif" class="input-file item-image-input">
            </div>
        </div>`).join('');
}

function syncItemsFromDom() {
    itemRows = [...itemEditor.querySelectorAll('.item-row')].map(row => ({
        konten: row.querySelector('.item-content').value,
        is_correct: row.querySelector('.item-key').value,
        correct_value: row.querySelector('.item-binary-key')?.value || null,
        id: row.querySelector('input[type="hidden"]').value || null,
    }));
}

itemEditor.addEventListener('input', () => { syncItemsFromDom(); renderPreview(); });
itemEditor.addEventListener('change', event => {
    if (event.target.matches('.item-binary-key')) event.target.closest('.item-row').querySelector('.item-key').value = event.target.value === 'A' ? '1' : '0';
    syncItemsFromDom(); renderPreview();
});
itemEditor.addEventListener('change', event => {
    if (!event.target.matches('.item-image-input') || !event.target.files?.[0]) return;
    const preview = event.target.closest('.item-row').querySelector('.item-image-preview');
    preview.src = URL.createObjectURL(event.target.files[0]);
    preview.classList.remove('hidden');
});
itemEditor.addEventListener('click', event => {
    const row = event.target.closest('.item-row');
    if (!row) return;
    const removeButton = event.target.closest('.remove-item');
    const moveUpButton = event.target.closest('.move-up');
    const moveDownButton = event.target.closest('.move-down');
    if (!removeButton && !moveUpButton && !moveDownButton) return;

    syncItemsFromDom();
    const index = Number(row.dataset.index);
    if (removeButton) itemRows.splice(index, 1);
    if (moveUpButton && index > 0) [itemRows[index - 1], itemRows[index]] = [itemRows[index], itemRows[index - 1]];
    if (moveDownButton && index < itemRows.length - 1) [itemRows[index + 1], itemRows[index]] = [itemRows[index], itemRows[index + 1]];
    renderItemEditor(); renderPreview();
});
document.getElementById('add-item').addEventListener('click', () => { syncItemsFromDom(); itemRows.push({konten:'',is_correct:'1'}); renderItemEditor(); });
typeSelect.addEventListener('change', renderQuestionType);

function renderTablePreview() {
    const target = document.getElementById('preview-table');
    const rows = document.getElementById('input-table').value.split(/\r?\n/).filter(Boolean).map(row => row.split('\t'));
    if (!rows.length) { target.innerHTML = ''; return; }
    target.innerHTML = `<div class="question-table-wrap"><table class="question-table"><thead><tr>${rows[0].map(cell => `<th>${escapeText(cell)}</th>`).join('')}</tr></thead><tbody>${rows.slice(1).map(row => `<tr>${row.map(cell => `<td>${escapeText(cell)}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`;
}

function renderPreview() {
    const content = document.getElementById('preview-soal');
    content.innerHTML = safePreviewHtml(document.getElementById('input-soal').value) || '<p>Preview teks soal...</p>';
    content.classList.add('question-content');
    renderTablePreview();
    const list = document.getElementById('preview-answers');
    if (typeSelect.value === 'pilihan_ganda') {
        list.innerHTML = ['A','B','C','D','E'].map(letter => {
            const value = document.getElementById(`input-opsi-${letter}`).value;
            return value ? `<div class="rounded-lg border p-3"><b>${letter}.</b> ${escapeText(value)}</div>` : '';
        }).join('');
    } else {
        syncItemsFromDom();
        const labels = [document.getElementById('option-label-a').value || 'A', document.getElementById('option-label-b').value || 'B'];
        list.innerHTML = itemRows.map((item, index) => `<div class="rounded-lg border p-3"><b>${index + 1}.</b> ${escapeText(item.konten)}${typeSelect.value === 'benar_salah' ? `<div class="mt-2 text-xs text-gray-500">○ ${escapeText(labels[0])} &nbsp; ○ ${escapeText(labels[1])}</div>` : ''}</div>`).join('');
    }
    if (typeof renderMathInElement === 'function') renderMathInElement(document.getElementById('question-preview'), {delimiters:[{left:'$$',right:'$$',display:true},{left:'$',right:'$',display:false}],throwOnError:false});
}

document.getElementById('sel-tipe-opsi').addEventListener('change', event => {
    const text = event.target.value !== 'gambar'; const image = event.target.value !== 'teks';
    document.querySelectorAll('.opsi-teks').forEach(el => el.classList.toggle('hidden', !text));
    document.querySelectorAll('.opsi-gambar').forEach(el => el.classList.toggle('hidden', !image));
});
const richEditor = document.getElementById('rich-editor');
const richInput = document.getElementById('input-soal');
function syncRichEditor() { richInput.value = richEditor.innerHTML; renderPreview(); }
richEditor.addEventListener('input', syncRichEditor);
document.querySelectorAll('.rich-command').forEach(button => button.addEventListener('click', () => {
    const [command, value] = button.dataset.command.split(':');
    if (command === 'createLink') {
        const url = prompt('Alamat tautan (https://...):');
        if (url) document.execCommand('createLink', false, url);
    } else if (command === 'insertTable') {
        document.execCommand('insertHTML', false, '<table><tbody><tr><th>Judul 1</th><th>Judul 2</th></tr><tr><td>Isi 1</td><td>Isi 2</td></tr></tbody></table><p><br></p>');
    } else document.execCommand(command, false, value || null);
    richEditor.focus(); syncRichEditor();
}));
function updateBinaryKeyLabels() {
    if (typeSelect.value !== 'benar_salah') return;
    const labelA = document.getElementById('option-label-a').value.trim() || 'Pilihan pertama';
    const labelB = document.getElementById('option-label-b').value.trim() || 'Pilihan kedua';
    itemEditor.querySelectorAll('.item-binary-key').forEach(select => {
        select.options[0].textContent = `${labelA} (pilihan pertama)`;
        select.options[1].textContent = `${labelB} (pilihan kedua)`;
    });
    renderPreview();
}
document.getElementById('option-label-a').addEventListener('input', updateBinaryKeyLabels);
document.getElementById('option-label-b').addEventListener('input', updateBinaryKeyLabels);
document.getElementById('input-table').addEventListener('input', renderPreview);
document.querySelectorAll('[id^="input-opsi-"]').forEach(el => el.addEventListener('input', renderPreview));
document.querySelectorAll('.image-preview-input').forEach(input => input.addEventListener('change', () => {
    const file = input.files?.[0];
    const preview = document.getElementById(input.dataset.preview);
    if (!file || !preview) return;
    preview.src = URL.createObjectURL(file);
    preview.classList.remove('hidden');
}));
document.getElementById('sel-tipe-opsi').dispatchEvent(new Event('change'));
renderQuestionType();
</script>
@endif
