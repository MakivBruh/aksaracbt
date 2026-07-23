import React, { useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';

const payload = JSON.parse(document.getElementById('session-display-data')?.textContent || '{}');
const sessions = Array.isArray(payload.sessions) ? payload.sessions : [];
const serverOffset = new Date(payload.serverNow).getTime() - Date.now();
const revealSeconds = Number(payload.revealSeconds || 120);

function secondsBetween(later, earlier) {
    return Math.max(0, Math.ceil((later - earlier) / 1000));
}

function formatDuration(totalSeconds) {
    const safe = Math.max(0, Number(totalSeconds) || 0);
    const hours = Math.floor(safe / 3600);
    const minutes = Math.floor((safe % 3600) / 60);
    const seconds = safe % 60;
    return [hours, minutes, seconds].map(value => String(value).padStart(2, '0')).join(':');
}

function chooseDefaultSession(items, now) {
    const running = items.find(item => new Date(item.startsAt) <= now && now < new Date(item.endsAt));
    if (running) return running.id;
    const upcoming = [...items].filter(item => new Date(item.startsAt) > now).sort((a, b) => new Date(a.startsAt) - new Date(b.startsAt))[0];
    return upcoming?.id || items[0]?.id || null;
}

function FullscreenIcon() {
    return <svg viewBox="0 0 24 24" className="h-5 w-5 fill-none stroke-current stroke-2"><path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5" /></svg>;
}

function SessionDisplay() {
    const initialNow = new Date(Date.now() + serverOffset);
    const [selectedId, setSelectedId] = useState(() => chooseDefaultSession(sessions, initialNow));
    const [nowMs, setNowMs] = useState(initialNow.getTime());
    const [isFullscreen, setIsFullscreen] = useState(Boolean(document.fullscreenElement));
    const [controlsVisible, setControlsVisible] = useState(true);
    const [alarmStopped, setAlarmStopped] = useState(false);
    const [alarmPlaying, setAlarmPlaying] = useState(false);
    const [audioBlocked, setAudioBlocked] = useState(false);
    const bellRef = useRef(null);

    useEffect(() => {
        const timer = window.setInterval(() => setNowMs(Date.now() + serverOffset), 250);
        const fullscreenHandler = () => setIsFullscreen(Boolean(document.fullscreenElement));
        document.addEventListener('fullscreenchange', fullscreenHandler);
        return () => {
            window.clearInterval(timer);
            document.removeEventListener('fullscreenchange', fullscreenHandler);
        };
    }, []);

    const session = useMemo(() => sessions.find(item => Number(item.id) === Number(selectedId)) || null, [selectedId]);
    const now = new Date(nowMs);
    const startsAt = session ? new Date(session.startsAt) : null;
    const endsAt = session ? new Date(session.endsAt) : null;
    const revealEndsAt = startsAt ? new Date(startsAt.getTime() + revealSeconds * 1000) : null;
    const phase = !session ? 'empty' : now < startsAt ? 'waiting' : now < revealEndsAt ? 'reveal' : now < endsAt ? 'running' : 'ended';

    const startBell = async () => {
        const bell = bellRef.current;
        if (!bell) return;
        bell.muted = false;
        bell.volume = 1;
        try {
            await bell.play();
            setAlarmPlaying(true);
            setAudioBlocked(false);
            setAlarmStopped(false);
        } catch {
            setAlarmPlaying(false);
            setAudioBlocked(true);
        }
    };

    const stopBell = () => {
        const bell = bellRef.current;
        if (bell) {
            bell.pause();
            bell.currentTime = 0;
        }
        setAlarmPlaying(false);
        setAudioBlocked(false);
        setAlarmStopped(true);
    };

    useEffect(() => {
        if (phase === 'ended' && !alarmStopped) startBell();
        if (phase !== 'ended' && alarmPlaying) stopBell();
    }, [phase, alarmStopped]);

    useEffect(() => {
        stopBell();
        setAlarmStopped(false);
    }, [selectedId]);

    useEffect(() => {
        const handleBellShortcut = event => {
            if (phase !== 'ended' || event.key.toLowerCase() !== 'h') return;
            event.preventDefault();
            if (alarmPlaying && !alarmStopped) stopBell();
            else startBell();
        };
        window.addEventListener('keydown', handleBellShortcut);
        return () => window.removeEventListener('keydown', handleBellShortcut);
    }, [phase, alarmPlaying, alarmStopped]);

    const unlockAudio = async () => {
        const bell = bellRef.current;
        if (!bell || phase === 'ended') return;
        bell.muted = true;
        try {
            await bell.play();
            bell.pause();
            bell.currentTime = 0;
        } catch {
            // Tombol manual tetap tersedia jika browser menolak aktivasi awal.
        } finally {
            bell.muted = false;
        }
    };

    const enterFullscreen = async () => {
        if (phase === 'ended') await startBell();
        else await unlockAudio();
        if (!document.fullscreenElement) await document.documentElement.requestFullscreen?.();
        else await document.exitFullscreen?.();
    };

    return <main className="relative flex min-h-screen w-full select-none flex-col overflow-hidden bg-slate-950 text-white">
        <audio ref={bellRef} src={payload.bellUrl || '/sounds/bell-ringing.mp3'} loop preload="auto" />
        <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(37,99,235,.35),transparent_38%),radial-gradient(circle_at_bottom_right,rgba(14,165,233,.22),transparent_40%)]" />
        <div className="pointer-events-none absolute inset-0 opacity-20 [background-image:linear-gradient(rgba(255,255,255,.04)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.04)_1px,transparent_1px)] [background-size:44px_44px]" />

        <header className={`relative z-20 flex items-center justify-between gap-4 p-4 transition-opacity sm:p-6 ${controlsVisible ? 'opacity-100' : 'opacity-0 hover:opacity-100'}`}>
            <div>
                <p className="text-xs font-bold uppercase tracking-[.35em] text-blue-300">Aksara CBT</p>
                <h1 className="mt-1 text-lg font-semibold sm:text-2xl">Layar Token & Timer</h1>
            </div>
            <div className="flex items-center gap-2">
                {sessions.length > 1 && <select value={selectedId || ''} onChange={event => setSelectedId(Number(event.target.value))} className="max-w-48 rounded-xl border border-white/20 bg-slate-900 px-3 py-2 text-sm text-white outline-none sm:max-w-xs">
                    {sessions.map(item => <option key={item.id} value={item.id}>{item.name}</option>)}
                </select>}
                <button onClick={() => setControlsVisible(value => !value)} className="hidden rounded-xl border border-white/20 bg-white/10 px-3 py-2 text-sm font-semibold sm:block">{controlsVisible ? 'Sembunyikan Kontrol' : 'Tampilkan Kontrol'}</button>
                <button onClick={enterFullscreen} className="flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold shadow-lg shadow-blue-950/30 hover:bg-blue-500"><FullscreenIcon />{isFullscreen ? 'Keluar' : 'Fullscreen'}</button>
                <a href={payload.backUrl || '/admin/sessions'} className="rounded-xl border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold">Kembali</a>
            </div>
        </header>

        <section className="relative z-10 flex flex-1 items-center justify-center px-5 pb-24 text-center sm:px-10">
            {phase === 'empty' && <div><p className="text-3xl font-bold">Belum ada sesi aktif</p><p className="mt-3 text-slate-400">Buat atau aktifkan token tryout terlebih dahulu.</p></div>}

            {phase === 'waiting' && <div className="max-w-5xl">
                <p className="text-sm font-bold uppercase tracking-[.4em] text-blue-300">Token dapat digunakan dalam</p>
                <p className="mt-6 font-mono text-[clamp(4rem,14vw,12rem)] font-black leading-none tabular-nums tracking-tight">{formatDuration(secondsBetween(startsAt, now))}</p>
                <p className="mt-8 text-xl text-slate-300 sm:text-3xl">{session.name}</p>
                <p className="mt-2 text-sm text-slate-500 sm:text-lg">Token akan ditampilkan ketika sesi dimulai</p>
            </div>}

            {phase === 'reveal' && <div className="animate-[pulse_2.8s_ease-in-out_infinite]">
                <p className="text-sm font-bold uppercase tracking-[.4em] text-emerald-300">Token ujian sudah aktif</p>
                <p className="mt-8 break-all font-mono text-[clamp(4rem,17vw,14rem)] font-black leading-none tracking-[.08em] text-white drop-shadow-[0_0_45px_rgba(59,130,246,.65)]">{session.token}</p>
                <p className="mt-10 text-lg text-slate-300 sm:text-2xl">Masukkan email peserta dan token di atas</p>
                <p className="mt-3 font-mono text-base text-blue-200">Tampilan utama berubah dalam {formatDuration(secondsBetween(revealEndsAt, now))}</p>
            </div>}

            {phase === 'running' && <div className="max-w-6xl">
                <p className="text-sm font-bold uppercase tracking-[.4em] text-blue-300">Sisa waktu ujian</p>
                <p className="mt-6 font-mono text-[clamp(5rem,18vw,15rem)] font-black leading-none tabular-nums tracking-tight">{formatDuration(secondsBetween(endsAt, now))}</p>
                <p className="mt-8 text-xl text-slate-300 sm:text-3xl">{session.name}</p>
            </div>}

            {phase === 'ended' && <div>
                <p className="text-sm font-bold uppercase tracking-[.4em] text-rose-300">Waktu telah habis</p>
                <p className="mt-7 text-[clamp(3.5rem,10vw,9rem)] font-black leading-none">UJIAN SELESAI</p>
                <p className="mt-8 text-xl text-slate-400">{session.name}</p>
            </div>}
        </section>

        {session && phase !== 'reveal' && <footer className="absolute inset-x-0 bottom-0 z-20 flex items-center justify-center border-t border-white/10 bg-slate-950/80 px-5 py-4 backdrop-blur-xl sm:py-5">
            <span className="mr-4 text-xs font-bold uppercase tracking-[.3em] text-slate-500">Token</span>
            <span className="font-mono text-2xl font-black tracking-[.16em] text-blue-200 sm:text-4xl">{phase === 'waiting' ? '••••••••' : session.token}</span>
        </footer>}
    </main>;
}

createRoot(document.getElementById('session-display-root')).render(<SessionDisplay />);
