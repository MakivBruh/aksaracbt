import React, { useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';

const PHASES = [
    { key: 'intro', label: 'Intro' },
    { key: 'top15', label: 'Top 15' },
    { key: 'harapan', label: 'Harapan' },
    { key: 'third', label: 'Juara 3' },
    { key: 'second', label: 'Juara 2' },
    { key: 'first', label: 'Juara 1' },
    { key: 'final', label: 'Final' },
];

function PodiumApp({ leaderboard, skorUrl }) {
    const [phase, setPhase] = useState(leaderboard.length ? 0 : 6);
    const [isFullscreen, setIsFullscreen] = useState(false);
    const stageRef = useRef(null);
    const canvasRef = useRef(null);

    const winners = useMemo(() => ({
        first: leaderboard[0] || null,
        second: leaderboard[1] || null,
        third: leaderboard[2] || null,
        harapan: leaderboard.slice(3, 6),
        others: leaderboard.slice(6, 15),
    }), [leaderboard]);

    const visible = {
        top15: phase >= 1,
        harapan: phase >= 2,
        third: phase >= 3,
        second: phase >= 4,
        first: phase >= 5,
        final: phase >= 6,
    };

    useEffect(() => {
        const onFullscreenChange = () => setIsFullscreen(Boolean(document.fullscreenElement));
        document.addEventListener('fullscreenchange', onFullscreenChange);
        return () => document.removeEventListener('fullscreenchange', onFullscreenChange);
    }, []);

    useEffect(() => {
        const onKeyDown = (event) => {
            if (event.key.toLowerCase() !== 'h') return;
            event.preventDefault();
            nextPhase();
        };

        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [leaderboard.length]);

    function startShowcase() {
        if (!leaderboard.length) return;
        setPhase(1);
    }

    function nextPhase() {
        if (!leaderboard.length) return;
        setPhase((current) => {
            if (current >= PHASES.length - 1) {
                replayCelebration();
                return current;
            }
            const next = Math.min(current + 1, PHASES.length - 1);
            if (next >= 5) fireConfetti(canvasRef.current);
            return next;
        });
    }

    function replayCelebration() {
        if (!leaderboard.length) return;
        fireConfetti(canvasRef.current, { burstCount: 190, duration: 185 });
    }

    function resetShowcase() {
        setPhase(0);
    }

    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            stageRef.current?.requestFullscreen?.();
            return;
        }
        document.exitFullscreen?.();
    }

    return (
        <section ref={stageRef} className="podium-react-stage">
            <style>{podiumStyles}</style>
            <canvas ref={canvasRef} className="podium-confetti" aria-hidden="true" />
            <div className="stage-light stage-light-a" />
            <div className="stage-light stage-light-b" />
            <div className="stage-grid" />

            <header className="podium-header">
                <div>
                    <p className="podium-kicker">Aksara CBT</p>
                    <h1>Podium 15 Teratas</h1>
                    <p className="podium-subtitle">
                        Ranking berdasarkan nilai akhir, total benar, lalu waktu submit tercepat.
                    </p>
                </div>

                <div className="podium-actions">
                    <a href={skorUrl} className="ghost-button">Rekap Nilai</a>
                    <button type="button" onClick={toggleFullscreen} className="ghost-button">
                        {isFullscreen ? 'Keluar Fullscreen' : 'Fullscreen'}
                    </button>
                    <button type="button" onClick={startShowcase} className="primary-button">
                        Mulai Showcase
                    </button>
                    <button type="button" onClick={nextPhase} className="ghost-button">
                        {visible.final ? 'Ulang Perayaan (H)' : 'Lanjut (H)'}
                    </button>
                    <button type="button" onClick={resetShowcase} className="ghost-button">Reset</button>
                </div>
            </header>

            {!leaderboard.length ? (
                <div className="empty-state">
                    Belum ada skor peserta. Hitung ulang skor dari halaman Rekap Nilai terlebih dahulu.
                </div>
            ) : (
                <main className="podium-layout">
                    <section className="main-show">
                        <PhaseRibbon phase={phase} />
                        <div className="champion-podium">
                            <WinnerPodium
                                label="Juara 2"
                                rank={2}
                                person={winners.second}
                                visible={visible.second || visible.final}
                                tone="silver"
                                height="medium"
                            />
                            <WinnerPodium
                                label="Juara 1"
                                rank={1}
                                person={winners.first}
                                visible={visible.first || visible.final}
                                tone="gold"
                                height="tall"
                                featured
                            />
                            <WinnerPodium
                                label="Juara 3"
                                rank={3}
                                person={winners.third}
                                visible={visible.third || visible.final}
                                tone="bronze"
                                height="short"
                            />
                        </div>

                        <div className={`hope-grid ${visible.harapan || visible.final ? 'is-visible' : ''}`}>
                            {winners.harapan.map((person, index) => (
                                <HopeCard
                                    key={person.id}
                                    person={person}
                                    rank={index + 4}
                                    label={`Harapan ${index + 1}`}
                                    visible={visible.harapan || visible.final}
                                />
                            ))}
                        </div>
                    </section>

                    <aside className={`rank-panel ${visible.top15 || visible.final ? 'is-visible' : ''}`}>
                        <h2>Top 15</h2>
                        <div className="rank-list">
                            {leaderboard.map((person, index) => (
                                <RankRow key={person.id} person={person} rank={index + 1} active={phase >= revealPhaseForRank(index + 1)} />
                            ))}
                        </div>
                    </aside>
                </main>
            )}
        </section>
    );
}

function PhaseRibbon({ phase }) {
    return (
        <div className="phase-ribbon">
            {PHASES.map((item, index) => (
                <span key={item.key} className={index <= phase ? 'active' : ''}>{item.label}</span>
            ))}
        </div>
    );
}

function WinnerPodium({ label, rank, person, visible, tone, height, featured = false }) {
    return (
        <article className={`winner-wrap ${visible ? 'is-visible' : ''} ${featured ? 'featured' : ''}`}>
            <div className={`winner-card ${tone}`}>
                <div className="medal">{rank}</div>
                <p className="winner-label">{label}</p>
                {!visible ? (
                    <LockedReveal title="? ? ?" />
                ) : person ? (
                    <>
                        <h2>{person.nama}</h2>
                        <p className="winner-school">{person.nama_sekolah || 'Sekolah belum diisi'}</p>
                        <p className="winner-meta">{person.no_ujian}</p>
                        <p className="winner-submit">Submit: {formatSubmitTime(person.selesai_ujian_at)}</p>
                        <p className="winner-score">{formatScore(person.nilai_akhir)}</p>
                        <p className="winner-small">{person.total_benar}/{person.total_soal} benar</p>
                    </>
                ) : (
                    <LockedReveal title="Belum tersedia" subtitle="Data peserta belum cukup" />
                )}
            </div>
            <div className={`winner-block ${tone} ${height}`}>
                <span>{rank}</span>
            </div>
        </article>
    );
}

function LockedReveal({ title, subtitle }) {
    return (
        <div className="locked-reveal">
            <div className="lock-mark">?</div>
            <h2>{title}</h2>
            <p>{subtitle}</p>
        </div>
    );
}

function HopeCard({ person, rank, label, visible }) {
    return (
        <article className={`hope-card ${visible ? 'is-visible' : ''}`}>
            <div>
                <p>{label}</p>
                {visible ? (
                    <>
                        <h3>{person.nama}</h3>
                        <span>{person.nama_sekolah || 'Sekolah belum diisi'}</span>
                        <span>{person.no_ujian} - Rank {rank}</span>
                        <span>Submit: {formatSubmitTime(person.selesai_ujian_at)}</span>
                    </>
                ) : (
                    <>
                        <h3 className="masked-name">? ? ?</h3>
                        
                    </>
                )}
            </div>
            <strong>{visible ? formatScore(person.nilai_akhir) : '--.--'}</strong>
        </article>
    );
}

function RankRow({ person, rank, active }) {
    return (
        <div className={`rank-row-react ${active ? 'active' : ''} ${rank <= 3 ? 'top-three' : ''}`}>
            <span className="rank-number">#{rank}</span>
            <div>
                <p>{active ? person.nama : '? ? ?'}</p>
                <span>
                    {active
                        ? `${person.nama_sekolah || 'Sekolah belum diisi'} - ${person.no_ujian} - ${formatSubmitTime(person.selesai_ujian_at)}`
                        : '? ? ?'}
                </span>
            </div>
            <strong>{active ? formatScore(person.nilai_akhir) : '--.--'}</strong>
        </div>
    );
}

function revealPhaseForRank(rank) {
    if (rank <= 3) return rank === 1 ? 5 : rank === 2 ? 4 : 3;
    if (rank <= 6) return 2;
    return 1;
}

function formatScore(value) {
    return Number(value || 0).toFixed(2);
}

function formatSubmitTime(value) {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        const parts = String(value).split(' ');
        return parts[1]?.slice(0, 8) || String(value);
    }
    return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

function fireConfetti(canvas, options = {}) {
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const rect = canvas.parentElement?.getBoundingClientRect();
    const width = Math.max(1, rect?.width || window.innerWidth);
    const height = Math.max(1, rect?.height || window.innerHeight);
    const dpr = window.devicePixelRatio || 1;
    canvas.width = width * dpr;
    canvas.height = height * dpr;
    canvas.style.width = `${width}px`;
    canvas.style.height = `${height}px`;
    ctx.scale(dpr, dpr);

    const colors = ['#fde68a', '#38bdf8', '#c4b5fd', '#fb7185', '#86efac', '#ffffff'];
    const pieces = Array.from({ length: options.burstCount || 130 }, () => ({
        x: Math.random() * width,
        y: -20 - Math.random() * height * 0.25,
        r: 4 + Math.random() * 7,
        vx: -2 + Math.random() * 4,
        vy: 3 + Math.random() * 5,
        rot: Math.random() * Math.PI,
        vr: -0.2 + Math.random() * 0.4,
        color: colors[Math.floor(Math.random() * colors.length)],
    }));

    let frame = 0;
    function tick() {
        frame += 1;
        ctx.clearRect(0, 0, width, height);
        pieces.forEach((piece) => {
            piece.x += piece.vx;
            piece.y += piece.vy;
            piece.rot += piece.vr;
            ctx.save();
            ctx.translate(piece.x, piece.y);
            ctx.rotate(piece.rot);
            ctx.fillStyle = piece.color;
            ctx.fillRect(-piece.r / 2, -piece.r / 2, piece.r, piece.r * 0.55);
            ctx.restore();
        });

        if (frame < (options.duration || 150)) requestAnimationFrame(tick);
        else ctx.clearRect(0, 0, width, height);
    }
    tick();
}

const podiumStyles = `
.podium-react-stage {
    position: relative;
    min-height: calc(100vh - 8rem);
    overflow: hidden;
    border-radius: 1.5rem;
    padding: 2rem;
    color: white;
    background:
        radial-gradient(circle at 16% 12%, rgba(250, 204, 21, .24), transparent 28rem),
        radial-gradient(circle at 86% 16%, rgba(56, 189, 248, .24), transparent 30rem),
        linear-gradient(135deg, #06101f 0%, #111827 44%, #0f172a 100%);
    box-shadow: 0 24px 70px rgba(2, 6, 23, .35);
}
.podium-react-stage:fullscreen { min-height: 100vh; height: 100vh; border-radius: 0; display: flex; flex-direction: column; }
.podium-confetti { position: absolute; inset: 0; z-index: 5; pointer-events: none; }
.stage-light { position: absolute; width: 28rem; height: 28rem; border-radius: 999px; filter: blur(30px); opacity: .35; animation: drift 9s ease-in-out infinite; }
.stage-light-a { left: -8rem; top: -10rem; background: #f59e0b; }
.stage-light-b { right: -10rem; top: -8rem; background: #38bdf8; animation-delay: -3s; }
.stage-grid { position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,.045) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.045) 1px, transparent 1px); background-size: 48px 48px; mask-image: linear-gradient(to bottom, black, transparent 88%); }
.podium-header { position: relative; z-index: 10; display: flex; gap: 1rem; align-items: flex-start; justify-content: space-between; }
.podium-kicker { font-size: .72rem; font-weight: 800; letter-spacing: .35em; text-transform: uppercase; color: #bfdbfe; }
.podium-header h1 { margin-top: .75rem; font-size: clamp(2rem, 5vw, 4.5rem); line-height: 1; font-weight: 950; letter-spacing: 0; }
.podium-subtitle { margin-top: .75rem; max-width: 42rem; color: #cbd5e1; }
.podium-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .5rem; }
.primary-button, .ghost-button { border-radius: .9rem; padding: .72rem 1rem; font-weight: 800; font-size: .86rem; transition: transform .2s ease, background .2s ease; }
.primary-button { background: white; color: #0f172a; box-shadow: 0 16px 40px rgba(255,255,255,.18); }
.ghost-button { background: rgba(255,255,255,.1); color: white; border: 1px solid rgba(255,255,255,.14); backdrop-filter: blur(14px); }
.primary-button:hover, .ghost-button:hover { transform: translateY(-2px); }
.empty-state { position: relative; z-index: 10; margin-top: 3rem; padding: 3rem; border: 1px solid rgba(255,255,255,.12); border-radius: 1.5rem; background: rgba(255,255,255,.08); text-align: center; color: #cbd5e1; }
.podium-layout { position: relative; z-index: 10; display: grid; grid-template-columns: minmax(0, 1fr) 22rem; gap: 1.5rem; margin-top: 2rem; }
.podium-react-stage:fullscreen .podium-layout { flex: 1; min-height: 0; align-items: stretch; }
.main-show { min-width: 0; }
.podium-react-stage:fullscreen .main-show { display: grid; min-height: 0; grid-template-rows: auto minmax(0, 1fr) auto; gap: .9rem; }
.phase-ribbon { display: flex; gap: .45rem; flex-wrap: wrap; margin-bottom: 1.25rem; }
.phase-ribbon span { border: 1px solid rgba(255,255,255,.12); border-radius: 999px; padding: .35rem .7rem; color: #94a3b8; font-size: .75rem; font-weight: 800; background: rgba(255,255,255,.06); }
.phase-ribbon span.active { color: #0f172a; background: #fde68a; border-color: #fde68a; }
.champion-podium { min-height: 34rem; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); align-items: end; gap: 1rem; }
.podium-react-stage:fullscreen .champion-podium { min-height: 0; max-height: none; align-self: end; }
.winner-wrap { opacity: .62; transform: translateY(26px) scale(.96); filter: saturate(.75); transition: opacity .65s ease, transform .65s cubic-bezier(.2,.8,.2,1), filter .65s ease; }
.winner-wrap.is-visible { opacity: 1; transform: translateY(0) scale(1); filter: none; }
.winner-wrap.featured.is-visible { animation: heroPulse 3.2s ease-in-out infinite; }
.winner-card { min-height: 17rem; border: 1px solid rgba(255,255,255,.12); border-radius: 1.5rem; padding: 1.25rem; text-align: center; background: rgba(255,255,255,.1); box-shadow: 0 24px 60px rgba(0,0,0,.28); backdrop-filter: blur(18px); }
.podium-react-stage:fullscreen .winner-card { min-height: 13.5rem; padding: .9rem; }
.medal { width: 4rem; height: 4rem; display: grid; place-items: center; margin: 0 auto; border-radius: 1rem; color: #0f172a; background: white; font-size: 1.8rem; font-weight: 950; box-shadow: 0 20px 50px rgba(255,255,255,.2); }
.winner-label { margin-top: 1rem; color: #bfdbfe; font-size: .72rem; text-transform: uppercase; letter-spacing: .18em; font-weight: 900; }
.winner-card h2 { margin-top: .7rem; margin-bottom: .55rem; font-size: clamp(1.1rem, 2vw, 1.7rem); line-height: 1.16; font-weight: 950; }
.winner-school { color: #ffffff; font-size: .86rem; line-height: 1.35; margin-top: .25rem; font-weight: 750; }
.winner-meta, .winner-submit, .winner-small { color: #cbd5e1; font-size: .78rem; line-height: 1.45; margin-top: .35rem; }
.winner-score { margin-top: 1rem; color: #fde68a; font-size: clamp(2.2rem, 4vw, 3.8rem); line-height: 1; font-weight: 1000; }
.locked-reveal { margin-top: 1rem; display: grid; justify-items: center; gap: .45rem; color: #dbeafe; }
.lock-mark { width: 3.4rem; height: 3.4rem; display: grid; place-items: center; border-radius: 1rem; color: #0f172a; background: linear-gradient(135deg, #e0f2fe, #fde68a); font-size: 1.6rem; font-weight: 1000; box-shadow: 0 16px 34px rgba(255,255,255,.14); }
.locked-reveal h2 { margin-top: .2rem; color: #ffffff; font-size: clamp(1rem, 1.8vw, 1.45rem); }
.locked-reveal p { max-width: 14rem; color: #cbd5e1; font-size: .78rem; }
.winner-block { position: relative; overflow: hidden; transform-origin: bottom; border-radius: 1.4rem 1.4rem 0 0; box-shadow: inset 0 1px rgba(255,255,255,.55), 0 26px 60px rgba(0,0,0,.32); transition: height .6s ease; }
.winner-wrap.is-visible .winner-block { animation: blockRise .85s cubic-bezier(.2,.85,.2,1) both; }
.winner-block span { position: absolute; inset: auto 0 .8rem; text-align: center; color: rgba(15,23,42,.42); font-size: 3.7rem; line-height: 1; font-weight: 1000; }
.winner-block.tall span { bottom: 1.2rem; font-size: 4.45rem; }
.winner-block.gold { background: linear-gradient(#fde68a, #f59e0b); }
.winner-block.silver { background: linear-gradient(#e2e8f0, #64748b); }
.winner-block.bronze { background: linear-gradient(#fdba74, #c2410c); }
.winner-block.tall { height: 13rem; }
.winner-block.medium { height: 9.5rem; }
.winner-block.short { height: 7.2rem; }
.podium-react-stage:fullscreen .winner-block.tall { height: clamp(7.5rem, 14vh, 12rem); }
.podium-react-stage:fullscreen .winner-block.medium { height: clamp(5.8rem, 10vh, 9rem); }
.podium-react-stage:fullscreen .winner-block.short { height: clamp(4.6rem, 8vh, 7rem); }
.podium-react-stage:fullscreen .winner-block span { bottom: .45rem; font-size: clamp(2.4rem, 5vh, 3.25rem); }
.podium-react-stage:fullscreen .winner-block.tall span { bottom: .9rem; font-size: clamp(3rem, 6.8vh, 4.2rem); }
.hope-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; margin-top: 1.25rem; opacity: .16; transform: translateY(18px); transition: opacity .55s ease, transform .55s ease; }
.podium-react-stage:fullscreen .hope-grid { margin-top: 0; align-self: end; }
.podium-react-stage:fullscreen .hope-card { padding: .85rem; }
.podium-react-stage:fullscreen .hope-card h3 { font-size: clamp(1rem, 1.45vw, 1.25rem); }
.podium-react-stage:fullscreen .hope-card strong { font-size: 1.35rem; }
.hope-grid.is-visible { opacity: 1; transform: translateY(0); }
.hope-card { display: flex; justify-content: space-between; gap: 1rem; border: 1px solid rgba(255,255,255,.12); border-radius: 1.25rem; background: rgba(255,255,255,.09); padding: 1rem; backdrop-filter: blur(16px); transition: transform .35s ease, border-color .35s ease, background .35s ease, box-shadow .35s ease; }
.hope-card.is-visible { border-color: rgba(221,214,254,.48); background: linear-gradient(135deg, rgba(255,255,255,.16), rgba(124,58,237,.18)); box-shadow: 0 22px 48px rgba(15,23,42,.28); transform: translateY(-4px); }
.hope-card p { color: #ddd6fe; font-size: .75rem; text-transform: uppercase; letter-spacing: .12em; font-weight: 900; }
.hope-card h3 { margin-top: .5rem; margin-bottom: .45rem; color: #ffffff; font-size: clamp(1.05rem, 1.8vw, 1.45rem); line-height: 1.18; font-weight: 950; }
.hope-card span { display: block; margin-top: .3rem; color: #cbd5e1; font-size: .74rem; line-height: 1.4; }
.hope-card strong { align-self: flex-start; border-radius: 1rem; padding: .45rem .65rem; color: #0f172a; background: #fef3c7; font-size: 1.65rem; line-height: 1; box-shadow: 0 14px 32px rgba(250,204,21,.2); }
.masked-name { color: #cbd5e1 !important; }
.rank-panel { opacity: .18; transform: translateX(24px); transition: opacity .55s ease, transform .55s ease; border: 1px solid rgba(255,255,255,.12); border-radius: 1.5rem; background: rgba(255,255,255,.09); padding: 1.25rem; backdrop-filter: blur(18px); }
.rank-panel.is-visible { opacity: 1; transform: translateX(0); }
.rank-panel h2 { font-size: 1.2rem; font-weight: 950; }
.rank-list { display: grid; gap: .55rem; margin-top: 1rem; }
.rank-row-react { opacity: .28; display: grid; grid-template-columns: auto minmax(0,1fr) auto; align-items: center; gap: .75rem; border-radius: 1rem; padding: .72rem; background: rgba(255,255,255,.08); transform: translateX(12px); transition: opacity .35s ease, transform .35s ease, background .35s ease; }
.rank-row-react.active { opacity: 1; transform: translateX(0); background: rgba(255,255,255,.13); }
.rank-row-react.top-three.active { background: rgba(250,204,21,.18); }
.rank-number { width: 2.15rem; height: 2.15rem; display: grid; place-items: center; border-radius: .75rem; color: #0f172a; background: #e0f2fe; font-size: .78rem; font-weight: 950; }
.rank-row-react p { font-weight: 850; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rank-row-react span:not(.rank-number) { color: #cbd5e1; font-size: .72rem; }
.rank-row-react strong { color: #fef3c7; }
@keyframes blockRise { from { transform: scaleY(.16); opacity: .45; } to { transform: scaleY(1); opacity: 1; } }
@keyframes heroPulse { 0%,100% { filter: drop-shadow(0 0 0 rgba(250,204,21,0)); } 50% { filter: drop-shadow(0 0 26px rgba(250,204,21,.4)); } }
@keyframes drift { 0%,100% { transform: translate(0,0) scale(1); } 50% { transform: translate(30px, 26px) scale(1.12); } }
@media (max-width: 1100px) { .podium-layout { grid-template-columns: 1fr; } .rank-panel { transform: none; } }
@media (max-height: 820px) {
    .podium-react-stage:fullscreen { overflow-y: auto; }
    .podium-react-stage:fullscreen .champion-podium { min-height: 24rem; }
    .podium-react-stage:fullscreen .hope-grid { margin-top: 1rem; }
}
@media (min-height: 900px) {
    .podium-react-stage:fullscreen .champion-podium { min-height: 0; }
}
@media (max-width: 1700px) and (min-width: 1101px) {
    .podium-react-stage:fullscreen { padding: 1.5rem; }
    .podium-react-stage:fullscreen .podium-header h1 { font-size: clamp(2.6rem, 4.2vw, 4.2rem); }
    .podium-react-stage:fullscreen .podium-subtitle { margin-top: .5rem; }
    .podium-react-stage:fullscreen .primary-button,
    .podium-react-stage:fullscreen .ghost-button { padding: .62rem .85rem; font-size: .78rem; }
    .podium-react-stage:fullscreen .podium-layout { grid-template-columns: minmax(0, 1fr) 23rem; gap: 1.15rem; margin-top: 1.25rem; }
    .podium-react-stage:fullscreen .phase-ribbon { margin-bottom: .65rem; }
    .podium-react-stage:fullscreen .winner-card { min-height: 12rem; padding: .75rem; }
    .podium-react-stage:fullscreen .medal { width: 3.25rem; height: 3.25rem; font-size: 1.45rem; }
    .podium-react-stage:fullscreen .winner-label { margin-top: .65rem; }
    .podium-react-stage:fullscreen .winner-card h2 { margin-top: .45rem; margin-bottom: .3rem; font-size: clamp(1rem, 1.6vw, 1.45rem); }
    .podium-react-stage:fullscreen .winner-school { font-size: .78rem; }
    .podium-react-stage:fullscreen .winner-meta,
    .podium-react-stage:fullscreen .winner-submit,
    .podium-react-stage:fullscreen .winner-small { font-size: .72rem; margin-top: .2rem; }
    .podium-react-stage:fullscreen .winner-score { margin-top: .55rem; font-size: clamp(2rem, 3.2vw, 3.1rem); }
    .podium-react-stage:fullscreen .winner-block.tall { height: clamp(6.5rem, 12vh, 10rem); }
    .podium-react-stage:fullscreen .winner-block.medium { height: clamp(5rem, 9vh, 7.75rem); }
    .podium-react-stage:fullscreen .winner-block.short { height: clamp(4rem, 7vh, 6.1rem); }
    .podium-react-stage:fullscreen .hope-grid { gap: .75rem; }
    .podium-react-stage:fullscreen .hope-card { padding: .75rem; }
    .podium-react-stage:fullscreen .hope-card h3 { margin-top: .35rem; margin-bottom: .25rem; }
    .podium-react-stage:fullscreen .hope-card span { margin-top: .2rem; font-size: .7rem; }
}
@media (max-width: 760px) { .podium-react-stage { padding: 1rem; } .podium-header { flex-direction: column; } .champion-podium, .hope-grid { grid-template-columns: 1fr; } .winner-block { height: 5rem !important; } }
`;

const root = document.getElementById('podium-react-root');
const dataScript = document.getElementById('podium-leaderboard-data');

if (root) {
    createRoot(root).render(
        <PodiumApp
            leaderboard={JSON.parse(dataScript?.textContent || '[]')}
            skorUrl={root.dataset.skorUrl || '#'}
        />
    );
}
