@extends('layouts.exam')

@section('title', 'Ujian - Aksara CBT')
@section('body_class', 'exam-interface')

@section('content')
    @include('exam.partials.subject-selection')
    @include('exam.partials.exam-shell')
    @include('exam.partials.submit-modal')
    @include('exam.partials.warning-modal')
@endsection

@push('scripts')
<script>
const TOKEN = localStorage.getItem('exam_token');
const BATAS_PELANGGARAN = 3;
const WAJIB_CODES = ['IND', 'ING', 'MAW'];
const SUBJECT_ICONS = {
    IND: 'BI',
    ING: 'EN',
    MAW: 'MT',
    MAL: 'MT',
    FIS: 'FS',
    KIM: 'KM',
    BIO: 'BL',
    EKO: 'EK',
    GEO: 'GE',
    SOS: 'SO',
    SEJ: 'SJ',
    INF: 'IF',
};

if (!TOKEN) {
    location.href = '/';
}

const state = {
    peserta: null,
    questions: [],
    subjects: {},
    answers: {},
    reviews: {},
    selectedSubject: null,
    currentIndex: 0,
    sisaDetik: 0,
    timerHandle: null,
    debounceTimers: {},
    pelanggaranCount: 0,
    antiCheatReady: false,
    examModeStarted: false,
    isSubmitting: false,
    violationCooldowns: {},
    wakeLock: null,
    wakeLockSupported: 'wakeLock' in navigator,
};

const API = (path, opts = {}) => fetch('/api' + path, {
    method: opts.method || 'GET',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${TOKEN}`,
    },
    body: opts.body ? JSON.stringify(opts.body) : undefined,
}).then(async response => {
    if (response.status === 401) {
        paksaRedirect();
        return Promise.reject(new Error('Unauthenticated'));
    }

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(data.message || 'Terjadi kesalahan.');
    }

    return data;
});

const byId = id => document.getElementById(id);
const allById = id => Array.from(document.querySelectorAll(`[id="${id}"]`));
const setText = (id, value) => allById(id).forEach(el => { el.textContent = value; });
const setHtml = (id, value) => allById(id).forEach(el => { el.innerHTML = value; });
const paksaRedirect = () => {
    state.isSubmitting = true;
    releaseWakeLock();
    localStorage.removeItem('exam_token');
    location.href = '/';
};

refreshDisplaySettings();
initExam();

async function initExam() {
    try {
        const [peserta, data] = await Promise.all([
            API('/me'),
            API('/soal'),
        ]);

        state.peserta = peserta;
        state.questions = Array.isArray(data.soals) ? data.soals : [];
        state.answers = normalizeAnswerMap(data.jawabans || {});
        state.sisaDetik = Number(peserta.sisa_detik || 0);
        state.reviews = loadReviews();
        state.subjects = groupSubjects(state.questions);

        setText('subject-participant', participantLabel(peserta));
        setText('exam-participant', participantLabel(peserta));
        renderSubjectSelection();
        startTimer();
        setupAntiCheat();
        showExamModeGate();
    } catch (error) {
        const loading = byId('subject-loading');
        if (loading) {
            loading.innerHTML = `
                <p class="text-sm font-semibold text-red-600">Gagal memuat ujian.</p>
                <p class="mt-1 text-sm text-slate-500">${escapeHtml(error.message || 'Silakan login ulang.')}</p>
            `;
        }
    }
}

function normalizeAnswerMap(raw) {
    return Object.entries(raw).reduce((answers, [key, value]) => {
        if (value) answers[String(key)] = value;
        return answers;
    }, {});
}

function isQuestionAnswered(question) {
    const answer = state.answers[String(question.id)];
    if (question.tipe_soal === 'benar_salah') {
        return question.items?.length > 0
            && answer && typeof answer === 'object' && !Array.isArray(answer)
            && question.items.every(item => Object.prototype.hasOwnProperty.call(answer, String(item.id)));
    }
    if (question.tipe_soal === 'pilih_semua') return Array.isArray(answer) && answer.length > 0;
    return typeof answer === 'string' && answer.length > 0;
}

function participantLabel(peserta) {
    const detail = peserta.nama_sekolah || peserta.no_ujian || peserta.email || 'Data peserta';
    return `${peserta.nama || 'Peserta'} - ${detail}`;
}

function groupSubjects(questions) {
    return questions.reduce((subjects, question) => {
        const code = question.mata_pelajaran_kode || 'LAIN';

        if (!subjects[code]) {
            subjects[code] = {
                code,
                name: question.mata_pelajaran || 'Mata Pelajaran',
                questions: [],
            };
        }

        subjects[code].questions.push(question);
        return subjects;
    }, {});
}

function renderSubjectSelection() {
    const mandatory = byId('mandatory-subjects');
    const elective = byId('elective-subjects');

    if (!mandatory || !elective) return;

    const subjectList = Object.values(state.subjects);
    const mandatorySubjects = subjectList.filter(subject => WAJIB_CODES.includes(subject.code));
    const electiveSubjects = subjectList.filter(subject => !WAJIB_CODES.includes(subject.code));
    const totalAnswered = state.questions.filter(isQuestionAnswered).length;

    mandatory.innerHTML = mandatorySubjects.length
        ? mandatorySubjects.map(renderSubjectCard).join('')
        : renderEmptySubject('Belum ada mata pelajaran wajib.');

    elective.innerHTML = electiveSubjects.length
        ? electiveSubjects.map(renderSubjectCard).join('')
        : renderEmptySubject('Belum ada mata pelajaran pilihan.');

    setText('subject-total-answered', totalAnswered);
    setText('subject-total-questions', state.questions.length);
    byId('subject-loading')?.classList.add('hidden');
    byId('subject-content')?.classList.remove('hidden');
    refreshDisplaySettings();
}

function renderEmptySubject(message) {
    return `
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">
            ${message}
        </div>
    `;
}

function renderSubjectCard(subject) {
    const total = subject.questions.length;
    const answered = subject.questions.filter(isQuestionAnswered).length;
    const status = answered === total && total > 0 ? 'Selesai' : answered > 0 ? 'Sedang Dikerjakan' : 'Belum Dikerjakan';
    const statusClass = {
        'Selesai': 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'Sedang Dikerjakan': 'bg-blue-50 text-blue-700 ring-blue-100',
        'Belum Dikerjakan': 'bg-slate-100 text-slate-600 ring-slate-200',
    }[status];
    return `
        <button type="button"
                onclick="openSubject('${escapeAttribute(subject.code)}')"
                class="group rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-600">
            <div class="flex items-start justify-between gap-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-sm font-bold text-blue-700 ring-1 ring-blue-100">
                    ${escapeHtml(SUBJECT_ICONS[subject.code] || subject.code.slice(0, 2))}
                </span>
                <span class="rounded-full px-3 py-1 text-xs font-semibold ring-1 ${statusClass}">
                    ${status}
                </span>
            </div>
            <h3 class="mt-5 text-lg font-semibold text-slate-950">${escapeHtml(subject.name)}</h3>
            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-xs text-slate-500">Jumlah Soal</p>
                    <p class="mt-1 font-semibold text-slate-900">${total} soal</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-xs text-slate-500">Terjawab</p>
                    <p class="mt-1 font-semibold text-slate-900">${answered}/${total} soal</p>
                </div>
            </div>
        </button>
    `;
}

function openSubject(code) {
    if (!state.subjects[code]) return;
    if (!ensureExamMode()) return;

    state.selectedSubject = code;
    state.currentIndex = 0;
    byId('subject-view')?.classList.add('hidden');
    byId('exam-view')?.classList.remove('hidden');
    renderExam();
}

function showSubjectSelection() {
    closeMobileNav();
    byId('exam-view')?.classList.add('hidden');
    byId('subject-view')?.classList.remove('hidden');
    renderSubjectSelection();
}

function getCurrentQuestions() {
    return state.selectedSubject ? (state.subjects[state.selectedSubject]?.questions || []) : [];
}

function getCurrentQuestion() {
    return getCurrentQuestions()[state.currentIndex] || null;
}

function renderExam() {
    const subject = state.subjects[state.selectedSubject];
    const question = getCurrentQuestion();

    if (!subject || !question) return;

    setText('exam-title', subject.name);
    setText('question-eyebrow', `${subject.name} - Soal ${state.currentIndex + 1} dari ${subject.questions.length}`);
    setText('question-heading', `Soal Nomor ${state.currentIndex + 1}`);
    setHtml('question-content', question.teks_soal
        ? question.teks_soal
        : '<p class="text-slate-500">Soal belum memiliki teks.</p>');
    renderQuestionTable(question);
    renderQuestionImage(question);
    renderOptions(question);
    renderReviewButton(question);
    renderNavigator();
    renderProgress();
    renderBottomButtons();
    renderMath();
}

function renderQuestionTable(question) {
    const rows = Array.isArray(question.tabel_data) ? question.tabel_data : [];
    const html = rows.length ? `
        <table class="min-w-full border-collapse text-sm">
            <thead><tr>${rows[0].map(cell => `<th class="min-w-28 border border-slate-300 bg-slate-100 px-3 py-2 text-left font-semibold">${escapeHtml(cell)}</th>`).join('')}</tr></thead>
            <tbody>${rows.slice(1).map(row => `<tr>${row.map(cell => `<td class="min-w-28 border border-slate-300 px-3 py-2">${escapeHtml(cell)}</td>`).join('')}</tr>`).join('')}</tbody>
        </table>` : '';
    allById('question-table').forEach(element => {
        element.innerHTML = html;
        element.classList.toggle('hidden', !html);
    });
}

function renderQuestionImage(question) {
    allById('question-image-wrap').forEach(wrap => {
        const image = wrap.querySelector('[id="question-image"]');

        if (question.gambar_soal && image) {
            image.src = question.gambar_soal;
            image.alt = `Gambar soal ${state.currentIndex + 1}`;
            wrap.classList.remove('hidden');
        } else {
            if (image) image.removeAttribute('src');
            wrap.classList.add('hidden');
        }
    });
}

function renderOptions(question) {
    const selected = state.answers[String(question.id)];
    if (question.tipe_soal === 'benar_salah') {
        const values = selected && !Array.isArray(selected) ? Object.fromEntries(Object.entries(selected).map(([id, value]) => [id, value === true || value === 1 || value === '1' ? 'A' : value === false || value === 0 || value === '0' ? 'B' : String(value).toUpperCase()])) : {};
        const labels = {A: question.option_label_a || 'Benar', B: question.option_label_b || 'Salah'};
        const html = (question.items || []).map((item, index) => `
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="leading-7 text-slate-800"><b>${index + 1}.</b> ${escapeHtml(item.konten)}</p>
                ${item.gambar ? `<img src="${escapeAttribute(item.gambar)}" alt="Gambar pernyataan ${index + 1}" class="mt-3 max-h-64 max-w-full rounded-lg border border-slate-200 object-contain">` : ''}
                <div class="mt-3 grid grid-cols-2 gap-2">
                    ${['A', 'B'].map(value => {
                        const active = Object.prototype.hasOwnProperty.call(values, String(item.id)) && values[String(item.id)] === value;
                        return `<button type="button" onclick="saveTrueFalse(${Number(question.id)}, ${Number(item.id)}, '${value}')" class="rounded-xl border px-4 py-3 font-semibold ${active ? 'border-blue-600 bg-blue-700 text-white' : 'border-slate-200 bg-slate-50 text-slate-700'}">${escapeHtml(labels[value])}</button>`;
                    }).join('')}
                </div>
            </div>`).join('');
        setHtml('option-list', html || '<p class="text-sm text-slate-500">Belum ada pernyataan.</p>');
        return;
    }

    if (question.tipe_soal === 'pilih_semua') {
        const values = Array.isArray(selected) ? selected.map(String) : [];
        const html = (question.items || []).map((item, index) => {
            const active = values.includes(String(item.id));
            return `<button type="button" onclick="toggleSelectedItem(${Number(question.id)}, ${Number(item.id)})" class="flex w-full items-start gap-3 rounded-2xl border p-4 text-left ${active ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-200' : 'border-slate-200 bg-white'}"><span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-md border ${active ? 'border-blue-700 bg-blue-700 text-white' : 'border-slate-300'}">${active ? '✓' : ''}</span><span class="min-w-0 leading-7 text-slate-800"><b>${index + 1}.</b> ${escapeHtml(item.konten)}${item.gambar ? `<img src="${escapeAttribute(item.gambar)}" alt="Gambar pernyataan ${index + 1}" class="mt-3 max-h-64 max-w-full rounded-lg border border-slate-200 object-contain">` : ''}</span></button>`;
        }).join('');
        setHtml('option-list', html || '<p class="text-sm text-slate-500">Belum ada pernyataan.</p>');
        return;
    }

    const options = Object.entries(question.opsi || {}).map(([letter, option]) => {
        const isSelected = selected === letter;
        const optionText = option?.teks || '';
        const image = option?.gambar
            ? `<img src="${escapeAttribute(option.gambar)}" alt="Gambar opsi ${escapeAttribute(letter)}" class="mt-3 max-h-[42svh] w-auto max-w-full rounded-lg border border-slate-200 object-contain sm:max-h-56">`
            : '';

        return `
            <button type="button"
                    onclick="saveAnswer(${Number(question.id)}, '${escapeAttribute(letter)}')"
                    class="option-card group flex min-h-[4.25rem] w-full items-start gap-3 rounded-2xl border p-4 text-left transition active:scale-[0.99] hover:border-blue-300 hover:bg-blue-50/60 sm:gap-4 ${isSelected ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-200' : 'border-slate-200 bg-white'}">
                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full border text-sm font-semibold ${isSelected ? 'border-blue-600 bg-blue-700 text-white' : 'border-slate-300 text-slate-600 group-hover:border-blue-400 group-hover:text-blue-700'}">
                    ${escapeHtml(letter)}
                </span>
                <span class="min-w-0 flex-1 text-base leading-7 text-slate-800 sm:text-sm sm:leading-6">
                    ${escapeHtml(optionText)}
                    ${image}
                </span>
            </button>
        `;
    }).join('');

    setHtml('option-list', options || '<p class="text-sm text-slate-500">Belum ada pilihan jawaban.</p>');
}

function renderReviewButton(question) {
    const isReviewed = Boolean(state.reviews[String(question.id)]);

    allById('review-button').forEach(button => {
        button.textContent = isReviewed ? 'Hapus Tanda Ragu' : 'Tandai Ragu-ragu';
        button.className = isReviewed
            ? 'min-h-11 rounded-xl border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 transition hover:bg-amber-100 sm:min-h-0'
            : 'min-h-11 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700 sm:min-h-0';
    });
}

function renderNavigator() {
    const questions = getCurrentQuestions();
    const html = questions.map((question, index) => {
        const id = String(question.id);
        const isCurrent = index === state.currentIndex;
        const isReviewed = Boolean(state.reviews[id]);
        const isAnswered = isQuestionAnswered(question);
        let classes = 'border-slate-200 bg-white text-slate-700 hover:border-blue-300 hover:bg-blue-50';

        if (isAnswered) classes = 'border-emerald-500 bg-emerald-500 text-white';
        if (isReviewed) classes = 'border-amber-400 bg-amber-400 text-slate-950';
        if (isCurrent) classes = 'border-blue-700 bg-blue-700 text-white ring-2 ring-blue-200';

        return `
            <button type="button"
                    onclick="goToQuestion(${index})"
                    class="aspect-square min-h-11 rounded-xl border text-sm font-semibold transition active:scale-95 ${classes}"
                    aria-label="Soal nomor ${index + 1}">
                ${index + 1}
            </button>
        `;
    }).join('');

    setHtml('question-navigator', html);
}

function renderProgress() {
    const questions = getCurrentQuestions();
    const answered = questions.filter(isQuestionAnswered).length;
    const reviewed = questions.filter(question => Boolean(state.reviews[String(question.id)])).length;
    const remaining = Math.max(questions.length - answered, 0);
    const percent = questions.length ? Math.round((answered / questions.length) * 100) : 0;

    setText('progress-percent', `${percent}%`);
    setText('progress-answered', answered);
    setText('progress-remaining', remaining);
    setText('progress-review', reviewed);
    allById('progress-bar').forEach(bar => { bar.style.width = `${percent}%`; });
}

function renderBottomButtons() {
    const questions = getCurrentQuestions();
    const isFirst = state.currentIndex === 0;
    const isLast = state.currentIndex === questions.length - 1;

    allById('prev-button').forEach(button => {
        button.disabled = isFirst;
    });

    allById('next-button').forEach(button => {
        button.textContent = isLast ? 'Selesai Mapel Ini' : 'Berikutnya';
    });
}

function saveAnswer(questionId, answer) {
    state.answers[String(questionId)] = answer;
    renderOptions(getCurrentQuestion());
    renderNavigator();
    renderProgress();
    renderSubjectSelection();
    renderMath();

    clearTimeout(state.debounceTimers[questionId]);
    state.debounceTimers[questionId] = setTimeout(() => {
        API('/jawaban', {
            method: 'POST',
            body: { soal_id: questionId, jawaban: answer },
        }).catch(error => console.error(error));
    }, 600);
}

function saveTrueFalse(questionId, itemId, value) {
    const current = state.answers[String(questionId)];
    const answer = current && typeof current === 'object' && !Array.isArray(current) ? {...current} : {};
    answer[String(itemId)] = value;
    saveAnswer(questionId, answer);
}

function toggleSelectedItem(questionId, itemId) {
    const current = Array.isArray(state.answers[String(questionId)]) ? [...state.answers[String(questionId)].map(String)] : [];
    const id = String(itemId);
    const answer = current.includes(id) ? current.filter(value => value !== id) : [...current, id];
    saveAnswer(questionId, answer);
}

function toggleReview() {
    const question = getCurrentQuestion();
    if (!question) return;

    const id = String(question.id);

    if (state.reviews[id]) {
        delete state.reviews[id];
    } else {
        state.reviews[id] = true;
    }

    persistReviews();
    renderReviewButton(question);
    renderNavigator();
    renderProgress();
    renderSubjectSelection();
}

function goToQuestion(index) {
    const questions = getCurrentQuestions();
    if (index < 0 || index >= questions.length) return;

    state.currentIndex = index;
    closeMobileNav();
    renderExam();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function goPrevQuestion() {
    goToQuestion(state.currentIndex - 1);
}

function goNextQuestion() {
    const questions = getCurrentQuestions();
    if (state.currentIndex >= questions.length - 1) {
        showSubjectSelection();
        return;
    }

    goToQuestion(state.currentIndex + 1);
}

function startTimer() {
    const tick = () => {
        const value = formatRemainingTime(state.sisaDetik);
        const timerClass = state.sisaDetik <= 300
            ? 'mt-0.5 font-mono text-xl font-semibold tabular-nums text-red-600 sm:mt-1 sm:text-2xl'
            : state.sisaDetik <= 900
                ? 'mt-0.5 font-mono text-xl font-semibold tabular-nums text-amber-600 sm:mt-1 sm:text-2xl'
                : 'mt-0.5 font-mono text-xl font-semibold tabular-nums text-slate-950 sm:mt-1 sm:text-2xl';

        allById('timer').forEach(timer => {
            timer.textContent = value;
            timer.className = timerClass;
        });

        if (state.sisaDetik <= 0) {
            clearInterval(state.timerHandle);
            submitUjian(true);
            return;
        }

        state.sisaDetik--;
    };

    clearInterval(state.timerHandle);
    tick();
    state.timerHandle = setInterval(tick, 1000);
}

function formatRemainingTime(totalSeconds) {
    const safeSeconds = Math.max(0, Number(totalSeconds || 0));
    const hours = Math.floor(safeSeconds / 3600);
    const minutes = Math.floor((safeSeconds % 3600) / 60);
    const seconds = safeSeconds % 60;

    if (hours > 0) {
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }

    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

function openSubmitDialog() {
    const allQuestions = state.questions;
    const subject = state.subjects[state.selectedSubject];
    const subjectQuestions = getCurrentQuestions();
    const subjectAnswered = subjectQuestions.filter(isQuestionAnswered).length;
    const answered = allQuestions.filter(isQuestionAnswered).length;
    const reviewed = allQuestions.filter(question => Boolean(state.reviews[String(question.id)])).length;
    const unanswered = Math.max(allQuestions.length - answered, 0);

    setText('submit-current-subject', subject?.name || 'Belum ada mapel yang dibuka');
    setText('submit-subject-answered', subjectAnswered);
    setText('submit-subject-total', subjectQuestions.length);
    setText('submit-answered', answered);
    setText('submit-unanswered', unanswered);
    setText('submit-review', reviewed);

    const modal = byId('submit-modal');
    modal?.classList.remove('hidden');
    modal?.classList.add('flex');
}

function closeSubmitDialog() {
    const modal = byId('submit-modal');
    modal?.classList.add('hidden');
    modal?.classList.remove('flex');
}

async function submitUjian(otomatis = false) {
    closeSubmitDialog();
    clearInterval(state.timerHandle);
    state.isSubmitting = true;
    releaseWakeLock();

    try {
        await API('/selesai', { method: 'POST' });
    } finally {
        localStorage.removeItem('exam_token');
        if (state.peserta?.id) localStorage.removeItem(reviewStorageKey());
        location.href = '/selesai';
    }
}

function setupAntiCheat() {
    if (state.antiCheatReady) return;
    state.antiCheatReady = true;

    hardenBrowserApis();

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            catatPelanggaran('keluar_halaman', 'Kamu terdeteksi meninggalkan halaman ujian.');
        } else if (state.examModeStarted) {
            requestWakeLock();
            setTimeout(() => ensureExamMode('Kamu kembali ke halaman ujian. Aktifkan lagi mode layar penuh.'), 250);
        }
    });

    document.addEventListener('fullscreenchange', () => {
        if (!state.examModeStarted || state.isSubmitting) return;

        if (isFullscreenActive()) {
            hideExamModeGate();
            return;
        }

        catatPelanggaran('keluar_fullscreen', 'Kamu keluar dari mode layar penuh.');
        showExamModeGate('Kamu keluar dari mode layar penuh. Tekan tombol di bawah untuk kembali ke ujian.');
    });

    window.addEventListener('blur', () => {
        if (!state.examModeStarted || state.isSubmitting) return;
        catatPelanggaran('window_blur', 'Fokus browser berpindah dari halaman ujian.');
    });

    window.addEventListener('pagehide', () => {
        if (!state.isSubmitting) {
            catatPelanggaranCepat('keluar_halaman', 'Halaman ujian ditutup atau dipindahkan.');
        }
    });

    window.addEventListener('beforeunload', event => {
        if (state.isSubmitting) return;
        catatPelanggaranCepat('keluar_halaman', 'Peserta mencoba memuat ulang atau menutup halaman ujian.');
        event.preventDefault();
        event.returnValue = '';
    });

    document.addEventListener('contextmenu', event => {
        event.preventDefault();
        showSoftWarning('Klik kanan atau tekan lama tidak diperbolehkan saat ujian. Ini hanya peringatan dan tidak dihitung sebagai pelanggaran.');
    });

    document.addEventListener('copy', event => {
        event.preventDefault();
        catatPelanggaran('salin_teks', 'Menyalin teks soal tidak diperbolehkan.');
    });

    document.addEventListener('cut', event => {
        event.preventDefault();
        catatPelanggaran('potong_teks', 'Memotong teks tidak diperbolehkan.');
    });

    document.addEventListener('paste', event => {
        event.preventDefault();
        catatPelanggaran('tempel_teks', 'Menempel teks tidak diperbolehkan.');
    });

    document.addEventListener('dragstart', event => {
        event.preventDefault();
        catatPelanggaran('drag_konten', 'Menyeret konten ujian tidak diperbolehkan.');
    });

    document.addEventListener('drop', event => {
        event.preventDefault();
        catatPelanggaran('drop_konten', 'Memasukkan konten dari luar tidak diperbolehkan.');
    });

    document.addEventListener('keydown', event => {
        const blocked = event.key === 'F12'
            || (event.ctrlKey && event.shiftKey && ['I', 'J', 'C'].includes(event.key.toUpperCase()))
            || (event.ctrlKey && ['A', 'C', 'F', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'V', 'X'].includes(event.key.toUpperCase()))
            || (event.metaKey && ['A', 'C', 'F', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'V', 'X'].includes(event.key.toUpperCase()))
            || event.key === 'PrintScreen';

        if (blocked) {
            event.preventDefault();
            catatPelanggaran('shortcut_terlarang', 'Shortcut tersebut tidak diperbolehkan.');
        }
    });

    window.addEventListener('resize', () => {
        if (!state.examModeStarted || state.isSubmitting) return;
        if (supportsFullscreen() && !isFullscreenActive()) {
            catatPelanggaran('resize_mencurigakan', 'Ukuran layar berubah saat tidak dalam mode layar penuh.');
        }
    });

    window.addEventListener('offline', () => {
        catatPelanggaran('offline', 'Koneksi perangkat terputus saat ujian.');
    });
}

async function catatPelanggaran(tipe, pesan) {
    if (!state.antiCheatReady || state.isSubmitting) return;

    const now = Date.now();
    const cooldownKey = violationCooldownKey(tipe);
    const cooldownMs = isExitPageViolation(tipe) ? 2200 : 900;

    if (state.violationCooldowns[cooldownKey] && now - state.violationCooldowns[cooldownKey] < cooldownMs) {
        return;
    }

    state.violationCooldowns[cooldownKey] = now;

    try {
        const result = await API('/log-pelanggaran', {
            method: 'POST',
            body: {
                tipe,
                pesan,
                metadata: collectSecurityMetadata(),
            },
        });

        state.pelanggaranCount = Number(result.total_pelanggaran || result.jumlah || state.pelanggaranCount + 1);
        const locked = Boolean(result.locked) || state.pelanggaranCount >= BATAS_PELANGGARAN;

        showWarning(pesan, state.pelanggaranCount, locked);

        if (locked) {
            setTimeout(() => {
                state.isSubmitting = true;
                releaseWakeLock();
                localStorage.removeItem('exam_token');
                location.href = '/';
            }, 3000);
        }
    } catch (error) {
        console.error(error);
    }
}

function catatPelanggaranCepat(tipe, pesan) {
    if (!state.antiCheatReady || state.isSubmitting) return;

    const now = Date.now();
    const cooldownKey = violationCooldownKey(tipe);
    if (state.violationCooldowns[cooldownKey] && now - state.violationCooldowns[cooldownKey] < 1800) return;
    state.violationCooldowns[cooldownKey] = now;

    const metadata = collectSecurityMetadata();

    if (navigator.sendBeacon) {
        const formData = new FormData();
        formData.append('tipe', tipe);
        formData.append('pesan', pesan);
        formData.append('metadata', JSON.stringify(metadata));
        navigator.sendBeacon(`/api/log-pelanggaran?token=${encodeURIComponent(TOKEN)}`, formData);
        return;
    }

    catatPelanggaran(tipe, pesan);
}

function violationCooldownKey(tipe) {
    return isExitPageViolation(tipe)
        ? 'keluar_page_group'
        : tipe;
}

function isExitPageViolation(tipe) {
    return ['keluar_fullscreen', 'keluar_halaman', 'window_blur', 'resize_mencurigakan'].includes(tipe);
}

function showWarning(message, total, locked) {
    setText('modal-pesan', locked
        ? 'Akses ujian dikunci karena batas pelanggaran tercapai.'
        : message
    );
    setText('modal-hitung', `Pelanggaran ${Math.min(total, BATAS_PELANGGARAN)} dari ${BATAS_PELANGGARAN}`);

    const modal = byId('modal-peringatan');
    modal?.classList.remove('hidden');
    modal?.classList.add('flex');
    playViolationWarningSound();
}

function playViolationWarningSound() {
    const audio = byId('suara-peringatan-pelanggaran');
    if (!audio) return;

    audio.muted = false;
    audio.volume = 1;
    audio.currentTime = 0;
    audio.play().catch(error => {
        console.warn('Suara peringatan tidak dapat diputar oleh browser.', error);
    });
}

function stopViolationWarningSound() {
    const audio = byId('suara-peringatan-pelanggaran');
    if (!audio) return;

    audio.pause();
    audio.currentTime = 0;
}

function unlockViolationWarningSound() {
    const audio = byId('suara-peringatan-pelanggaran');
    if (!audio || audio.dataset.unlocked === 'true') return;

    audio.muted = true;
    audio.volume = 1;

    const playAttempt = audio.play();
    if (!playAttempt) return;

    playAttempt.then(() => {
        audio.pause();
        audio.currentTime = 0;
        audio.muted = false;
        audio.dataset.unlocked = 'true';
    }).catch(() => {
        audio.muted = false;
    });
}

function showSoftWarning(message) {
    setText('modal-pesan', message);
    setText('modal-hitung', 'Tidak dihitung sebagai pelanggaran.');

    const modal = byId('modal-peringatan');
    modal?.classList.remove('hidden');
    modal?.classList.add('flex');
}

function tutupModal() {
    stopViolationWarningSound();

    const modal = byId('modal-peringatan');
    modal?.classList.add('hidden');
    modal?.classList.remove('flex');
}

function supportsFullscreen() {
    return typeof document.documentElement.requestFullscreen === 'function';
}

function isFullscreenActive() {
    return Boolean(document.fullscreenElement);
}

function showExamModeGate(message = null) {
    const gate = byId('exam-mode-gate');
    const text = byId('exam-mode-gate-message');

    if (message && text) text.textContent = message;
    gate?.classList.remove('hidden');
    gate?.classList.add('flex');
}

function hideExamModeGate() {
    const gate = byId('exam-mode-gate');
    gate?.classList.add('hidden');
    gate?.classList.remove('flex');
}

async function enterExamMode() {
    unlockViolationWarningSound();

    if (!supportsFullscreen()) {
        state.examModeStarted = true;
        hideExamModeGate();
        catatPelanggaran('fullscreen_tidak_didukung', 'Browser/perangkat tidak mendukung mode layar penuh.');
        return;
    }

    const success = await mintaFullscreen();

    if (!success) {
        showExamModeGate('Mode layar penuh gagal aktif. Izinkan fullscreen dari browser lalu tekan tombol ini lagi.');
        catatPelanggaran('fullscreen_gagal', 'Peserta gagal mengaktifkan mode layar penuh.');
    }
}

function ensureExamMode(message = null) {
    if (!supportsFullscreen()) {
        state.examModeStarted = true;
        hideExamModeGate();
        return true;
    }

    if (isFullscreenActive()) {
        state.examModeStarted = true;
        hideExamModeGate();
        return true;
    }

    showExamModeGate(message || 'Aktifkan mode layar penuh sebelum melanjutkan ujian.');
    return false;
}

function mintaFullscreen() {
    if (!supportsFullscreen()) return Promise.resolve(false);

    if (isFullscreenActive()) {
        state.examModeStarted = true;
        hideExamModeGate();
        requestWakeLock();
        return Promise.resolve(true);
    }

    return document.documentElement.requestFullscreen()
        .then(() => {
            state.examModeStarted = true;
            hideExamModeGate();
            requestWakeLock();
            return true;
        })
        .catch(() => false);
}

async function requestWakeLock() {
    if (!state.wakeLockSupported || state.isSubmitting || document.visibilityState !== 'visible') return;
    if (state.wakeLock && !state.wakeLock.released) return;

    try {
        state.wakeLock = await navigator.wakeLock.request('screen');
        state.wakeLock.addEventListener('release', () => {
            state.wakeLock = null;
        });
    } catch (error) {
        state.wakeLock = null;
    }
}

async function releaseWakeLock() {
    if (!state.wakeLock) return;

    try {
        await state.wakeLock.release();
    } catch (error) {
        // Wake Lock bisa sudah dilepas browser saat tab kehilangan fokus.
    } finally {
        state.wakeLock = null;
    }
}

function collectSecurityMetadata() {
    return {
        visibility_state: document.visibilityState,
        fullscreen: isFullscreenActive(),
        screen_width: window.screen?.width,
        screen_height: window.screen?.height,
        inner_width: window.innerWidth,
        inner_height: window.innerHeight,
        device_pixel_ratio: window.devicePixelRatio,
        platform: navigator.platform,
        user_agent: navigator.userAgent,
        selected_subject: state.selectedSubject,
        current_question_index: state.currentIndex,
        timestamp: new Date().toISOString(),
    };
}

function hardenBrowserApis() {
    if (window.__aksaraAntiCheatHardened) return;
    window.__aksaraAntiCheatHardened = true;

    const originalOpen = window.open;
    window.open = function (...args) {
        catatPelanggaran('popup_diblokir', 'Percobaan membuka pop-up atau tab baru terdeteksi.');
        return null;
    };

    const originalPrint = window.print;
    window.print = function (...args) {
        catatPelanggaran('print_diblokir', 'Mencetak halaman ujian tidak diperbolehkan.');
        return undefined;
    };
}

function openMobileNav() {
    byId('mobile-nav-backdrop')?.classList.remove('hidden');
    byId('mobile-nav')?.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    refreshDisplaySettings();
}

function closeMobileNav() {
    byId('mobile-nav-backdrop')?.classList.add('hidden');
    byId('mobile-nav')?.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

function reviewStorageKey() {
    return `aksara_reviews_${state.peserta?.id || 'guest'}`;
}

function loadReviews() {
    try {
        return JSON.parse(localStorage.getItem(reviewStorageKey()) || '{}') || {};
    } catch (error) {
        return {};
    }
}

function persistReviews() {
    localStorage.setItem(reviewStorageKey(), JSON.stringify(state.reviews));
}

function setFontScale(scale) {
    const allowed = ['small', 'normal', 'large'];
    const nextScale = allowed.includes(scale) ? scale : 'normal';

    document.documentElement.dataset.fontScale = nextScale;
    localStorage.setItem('aksara_font_scale', nextScale);
    refreshDisplaySettings();
}

function toggleTheme() {
    const current = document.documentElement.dataset.theme || 'light';
    const nextTheme = current === 'dark' ? 'light' : 'dark';

    document.documentElement.dataset.theme = nextTheme;
    localStorage.setItem('aksara_theme', nextTheme);
    refreshDisplaySettings();
}

function refreshDisplaySettings() {
    const fontScale = document.documentElement.dataset.fontScale || 'normal';
    const theme = document.documentElement.dataset.theme || 'light';

    document.querySelectorAll('[data-font-button]').forEach(button => {
        const active = button.dataset.fontButton === fontScale;
        button.className = active
            ? 'rounded-lg bg-blue-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition'
            : 'rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-white';
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });

    document.querySelectorAll('[data-theme-toggle]').forEach(button => {
        button.textContent = theme === 'dark' ? 'Mode Terang' : 'Mode Gelap';
        button.className = theme === 'dark'
            ? 'rounded-xl border border-slate-600 bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-100 transition hover:bg-slate-700'
            : 'rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-white';
        button.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
    });
}

function renderMath() {
    if (typeof renderMathInElement !== 'function') return;

    ['question-content', 'question-table', 'option-list'].forEach(id => {
        allById(id).forEach(element => {
            renderMathInElement(element, {
                delimiters: [
                    { left: '$$', right: '$$', display: true },
                    { left: '$', right: '$', display: false },
                ],
                throwOnError: false,
            });
        });
    });
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function escapeAttribute(value) {
    return escapeHtml(value).replaceAll('`', '&#096;');
}
</script>
@endpush
