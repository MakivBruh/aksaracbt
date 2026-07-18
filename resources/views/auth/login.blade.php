@extends('layouts.exam')

@section('title', 'Login Peserta - Aksara CBT')

@section('content')
<main class="min-h-screen bg-slate-100">
    <div class="mx-auto grid min-h-screen max-w-7xl items-center gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,1fr)_420px] lg:px-8">
        <section class="space-y-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-blue-700">Aksara CBT</p>
                <h1 class="mt-3 max-w-3xl text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                    Masuk Ujian Tryout TKA
                </h1>
                <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600">
                    Gunakan email peserta saat pendaftaran dan token sesi dari panitia. Pastikan data sudah benar sebelum masuk ke halaman ujian.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-slate-950">Token Sesi</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Token hanya dapat digunakan saat jadwal ujian sedang aktif.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-slate-950">Waktu Berjalan</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">90 menit tanpa tambahan waktu.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-slate-950">Pantauan Ujian</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Aktivitas keluar halaman atau shortcut terlarang akan tercatat.</p>
                </div>
            </div>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-blue-700">Tata Tertib</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-950">Sebelum Mulai Ujian</h2>
                    </div>
                    <p class="text-sm text-slate-500">Baca singkat agar ujian berjalan aman.</p>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        'Gunakan <strong>satu</strong> perangkat dan pastikan koneksi internet stabil selama ujian.',
                        'Login menggunakan email peserta saat pendaftaran dan token ujian dari panitia. Token hanya berlaku saat jadwal ujian aktif.',
                        'Semua jawaban akan tersimpan otomatis, tetapi pastikan koneksi internet tetap aktif.',
                        'Sebelum memilih mata pelajaran, aktifkan mode <strong>layar penuh</strong> yang diminta sistem.',
                        'Waktu ujian akan tetap berjalan meskipun peserta terlambat masuk dan tidak ada tambahan waktu.',
                        'Keluar halaman, keluar <strong>fullscreen</strong>, berpindah aplikasi, atau fokus browser berpindah akan dicatat sebagai pelanggaran.',
                        'Refresh, menutup halaman, atau membuka tab/jendela lain saat ujian juga dicatat sebagai <strong>pelanggaran</strong>.',
                        'Dilarang menyalin, memotong, menempel, menyeret, atau memasukkan konten dari luar halaman ujian.',
                        'Shortcut terlarang seperti refresh, print, find, buka tab baru, inspect/devtools, dan screenshot tidak diperbolehkan.',
                        'Percobaan membuka pop-up/tab baru, mencetak halaman, atau koneksi terputus saat ujian dapat tercatat oleh sistem.',
                        'Klik kanan atau tekan lama hanya diberi peringatan dan tidak dihitung sebagai pelanggaran.',
                        'Jika <strong>pelanggaran</strong> mencapai 3 kali, akses ujian akan <strong>dikunci</strong> dan hanya admin yang dapat membuka kembali.',
                        'Pilih mata pelajaran dari halaman utama ujian dan kirim seluruh ujian setelah semua jawaban diperiksa.',
                    ] as $rule)
                        <div class="flex gap-3 rounded-xl bg-slate-50 p-4">
                            <span class="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">!</span>
                            <p class="text-sm leading-6 text-slate-700">{!! $rule !!}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </section>

        <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl sm:p-8">
            <div class="text-center">
                <div class="mx-auto flex h-14 w-[110px] items-center justify-center rounded-2xl bg-blue-50 text-2xl font-bold text-blue-700">Aksara</div>
                <h2 class="mt-5 text-2xl font-semibold text-slate-950">Login Peserta</h2>
                <p class="mt-2 text-sm text-slate-500">Masukkan email saat pendaftaran dan token dari panitia.</p>
            </div>

            <div id="error-box" class="mt-6 hidden rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <span id="error-text"></span>
            </div>

            <div class="mt-6 space-y-4">
                <div>
                    <label for="email" class="mb-1 block text-sm font-semibold text-slate-700">Email Peserta</label>
                    <input id="email"
                           type="email"
                           placeholder="nama@email.com"
                           class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-center text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                           autocomplete="email"
                           autocorrect="off"
                           spellcheck="false">
                </div>

                <div>
                    <label for="token" class="mb-1 block text-sm font-semibold text-slate-700">Token Ujian</label>
                    <input id="token"
                           type="text"
                           placeholder="Contoh: AKUHEBAT"
                           class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-center font-mono text-lg uppercase tracking-widest text-slate-900 outline-none transition placeholder:font-sans placeholder:tracking-normal placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                           autocomplete="off"
                           autocorrect="off"
                           spellcheck="false">
                </div>

                <button id="btn-login"
                        onclick="doLogin()"
                        class="w-full rounded-xl bg-blue-700 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60">
                    Mulai Ujian
                </button>
            </div>

            <p class="mt-6 text-center text-xs leading-5 text-slate-400">
                Dengan masuk, peserta dianggap memahami tata tertib dan siap mengikuti ujian.
            </p>
        </aside>
    </div>
</main>
@endsection

@push('scripts')
<script>
document.getElementById('email').addEventListener('keydown', event => {
    if (event.key === 'Enter') document.getElementById('token').focus();
});

document.getElementById('token').addEventListener('keydown', event => {
    if (event.key === 'Enter') doLogin();
});

async function doLogin() {
    const email = document.getElementById('email').value.trim().toLowerCase();
    const token = document.getElementById('token').value.trim();
    const btn = document.getElementById('btn-login');

    if (!email) {
        tampilError('Masukkan email terlebih dahulu.');
        return;
    }

    if (!token) {
        tampilError('Masukkan token ujian terlebih dahulu.');
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Memverifikasi...';
    sembunyikanError();

    try {
        const res = await fetch('/api/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ email, token_login: token }),
        });
        const data = await res.json();

        if (!res.ok) {
            tampilError(data.message || 'Login gagal. Periksa kembali email dan token kamu.');
            return;
        }

        localStorage.setItem('exam_token', data.token);
        window.location.href = '/ujian';
    } catch {
        tampilError('Koneksi gagal. Periksa internet kamu dan coba lagi.');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Mulai Ujian';
    }
}

function tampilError(message) {
    document.getElementById('error-text').textContent = message;
    document.getElementById('error-box').classList.remove('hidden');
}

function sembunyikanError() {
    document.getElementById('error-box').classList.add('hidden');
}
</script>
@endpush
