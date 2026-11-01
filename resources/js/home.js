// The BioHop home: one scroll scene per feature, in the manner of the Amnesia site (GSAP ScrollTrigger + Lenis).
// GSAP and Lenis ship inside our own bundle (no CDN). Every scene is complete as plain HTML and CSS;
// this file only adds motion, and does nothing with "reduce motion" on.
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';

const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
const once = (trigger, start = 'top 78%') => ({ trigger, start, once: true });

function start() {
    gsap.registerPlugin(ScrollTrigger);
    document.documentElement.classList.add('bx-motion');

    // smooth scroll, kept in step with ScrollTrigger; scrollable boxes (the phones) keep their own wheel
    const lenis = new Lenis({ duration: 1.05, smoothWheel: true });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((t) => lenis.raf(t * 1000));
    gsap.ticker.lagSmoothing(0);
    $$('.bio-phone, .bx-phone').forEach((el) => el.setAttribute('data-lenis-prevent', ''));
    $$('a[href^="#"]').forEach((a) => a.addEventListener('click', (e) => {
        const id = a.getAttribute('href');
        const target = id.length > 1 && $(id);
        if (!target) return;
        e.preventDefault();
        lenis.scrollTo(target, { offset: -72 });
    }));

    heads(); marquee(); blocks(); story(); themes(); analytics(); share(); gates(); style(); install(); faq(); family();
    addEventListener('load', () => ScrollTrigger.refresh());
    document.fonts?.ready.then(() => ScrollTrigger.refresh());
}

/** a heading block: label, title, lead rise one after another */
function heads() {
    $$('.bx-head, .bx-final .bx-wrap > *').forEach((head) => {
        const parts = head.classList.contains('bx-head') ? Array.from(head.children) : [head];
        gsap.from(parts, { y: 26, opacity: 0, duration: .8, stagger: .09, ease: 'power3.out', scrollTrigger: once(head, 'top 86%') });
    });
}

function marquee() {
    const track = $('[data-bx-marq] > div');
    if (!track) return;
    track.innerHTML += track.innerHTML;
    Array.from(track.children).slice(track.children.length / 2).forEach((c) => c.setAttribute('aria-hidden', 'true'));
    track.classList.add('run');
    gsap.from('[data-bx-nums] > div', { y: 24, opacity: 0, duration: .7, stagger: .08, ease: 'power3.out', scrollTrigger: once('[data-bx-nums]', 'top 88%') });
}

/** 01: cards rise, then the little picture in each one starts to move */
function blocks() {
    const cells = $$('[data-bx-bento] .bx-cell');
    cells.forEach((cell, i) => {
        cell.addEventListener('pointermove', (e) => { const r = cell.getBoundingClientRect(); cell.style.setProperty('--mx', (e.clientX - r.left) + 'px'); cell.style.setProperty('--my', (e.clientY - r.top) + 'px'); });
        const tl = gsap.timeline({ scrollTrigger: once(cell, 'top 90%') });
        tl.from(cell, { y: 44, opacity: 0, duration: .7, ease: 'power3.out', delay: (i % 4) * .07 })
            .from($$('.bx-vis > *', cell), { y: 14, opacity: 0, duration: .5, stagger: .06, ease: 'power2.out' }, '-=.35');
        ScrollTrigger.create({ trigger: cell, start: 'top 95%', end: 'bottom 5%', onToggle: (self) => cell.classList.toggle('is-on', self.isActive) });
    });
}

/** 02: pinned. Name is typed, blocks arrive, the theme changes, the page is shared, the numbers come in. */
function story() {
    const sec = $('[data-bx-story]');
    if (!sec) return;
    const typed = $('[data-story-type]', sec), ok = $('[data-story-ok]', sec), phone = $('#story-phone', sec);
    const steps = $$('[data-bx-steps] li', sec), views = $('[data-story-views]', sec);
    const word = typed.textContent, o = { n: 0, v: 0 };
    typed.textContent = '';
    const palette = { a: ['#1e1b4b', '#7f1d1d', '#f1f5f9'], b: ['#7c3aed', '#f97316', '#cbd5e1'], t: ['#ffffff', '#ffffff', '#0f172a'], l: ['rgba(255,255,255,.16)', 'rgba(255,255,255,.2)', 'rgba(15,23,42,.08)'] };
    gsap.set(phone, { '--ph-a': palette.a[0], '--ph-b': palette.b[0], '--ph-t': palette.t[0], '--ph-l': palette.l[0] });

    const tl = gsap.timeline({ defaults: { ease: 'power2.out' } });
    tl.set(ok, { autoAlpha: 0, scale: .8 })
        .set($$('.bx-av, .bx-name, .bx-bio, .bx-link', phone), { autoAlpha: 0 })
        .set($$('.bx-qr, .bx-chip, .bx-stats', sec), { autoAlpha: 0 })
        // 1 claim
        .to(o, { n: word.length, duration: .9, ease: 'none', onUpdate: () => { typed.textContent = word.slice(0, Math.round(o.n)); } }, .05)
        .to(ok, { autoAlpha: 1, scale: 1, duration: .15, ease: 'back.out(2)' }, .98)
        // 2 blocks
        .to($$('.bx-av, .bx-name, .bx-bio', phone), { autoAlpha: 1, y: 0, startAt: { y: 14 }, duration: .3, stagger: .08 }, 1.05)
        .to($$('.bx-link', phone), { autoAlpha: 1, x: 0, startAt: { x: 90 }, duration: .35, stagger: .16 }, 1.2)
        // 3 style
        .to(phone, { '--ph-a': palette.a[1], '--ph-b': palette.b[1], duration: .45 }, 2.1)
        .to(phone, { '--ph-a': palette.a[2], '--ph-b': palette.b[2], '--ph-t': palette.t[2], '--ph-l': palette.l[2], duration: .45 }, 2.6)
        // 4 share
        .to($$('.bx-qr', sec), { autoAlpha: 1, y: 0, startAt: { y: 36 }, duration: .45 }, 3.1)
        .to($$('.bx-chip', sec), { autoAlpha: 1, scale: 1, startAt: { scale: .4 }, duration: .35, stagger: .1, ease: 'back.out(1.8)' }, 3.3)
        // 5 numbers
        .to($('.bx-stats', sec), { autoAlpha: 1, y: 0, startAt: { y: 30 }, duration: .4 }, 4.1)
        .from($$('.bx-bars i', sec), { scaleY: 0, transformOrigin: '50% 100%', duration: .4, stagger: .06 }, 4.2)
        .to(o, { v: 1284, duration: .7, ease: 'power1.out', onUpdate: () => { views.textContent = Math.round(o.v).toLocaleString(); } }, 4.2)
        .to({}, { duration: .35 }); // a short hold at the end

    const mark = (progress) => {
        const at = Math.min(steps.length - 1, Math.floor(progress * steps.length));
        steps.forEach((li, i) => { li.classList.toggle('on', i === at); li.classList.toggle('done', i < at); });
    };
    mark(0);
    const mm = gsap.matchMedia();
    mm.add('(min-width: 981px)', () => {
        ScrollTrigger.create({ animation: tl, trigger: sec, start: 'top 64px', end: '+=3600', pin: true, scrub: .7, onUpdate: (self) => mark(self.progress) });
    });
    mm.add('(max-width: 980px)', () => {
        ScrollTrigger.create({ animation: tl, trigger: $('.bx-story-vis', sec), start: 'top 70%', end: 'bottom 30%', scrub: .7, onUpdate: (self) => mark(self.progress) });
    });
}

/** 03: the phone changes theme while you scroll; a tap picks one */
function themes() {
    const sec = $('[data-bx-themes]');
    if (!sec) return;
    let data = {};
    try { data = JSON.parse($('#bx-themes-data', sec).textContent); } catch (e) { return; }
    const keys = Object.keys(data), phone = $('#theme-phone', sec), label = $('[data-theme-name]', sec), buttons = $$('[data-theme-key]', sec);
    let manual = false, current = '';
    const apply = (key) => {
        if (key === current || !data[key]) return;
        current = key;
        const t = data[key];
        phone.style.setProperty('--ph-bg', t.background);
        phone.style.setProperty('--ph-t', t.text);
        phone.style.setProperty('--ph-m', t.bio || t.text);
        phone.style.setProperty('--ph-l', t.link_bg);
        phone.style.setProperty('--ph-lt', t.link_text);
        phone.style.setProperty('--ph-lb', t.link_border || 'transparent');
        label.textContent = t.name || key;
        buttons.forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.themeKey === key)));
        gsap.fromTo(phone, { scale: .985 }, { scale: 1, duration: .5, ease: 'back.out(2)' });
    };
    buttons.forEach((b) => b.addEventListener('click', () => { manual = true; apply(b.dataset.themeKey); }));
    apply(keys[0]);
    ScrollTrigger.create({ trigger: sec, start: 'top 60%', end: 'bottom 55%', onUpdate: (self) => { if (!manual) apply(keys[Math.min(keys.length - 1, Math.floor(self.progress * keys.length))]); } });
    gsap.from(buttons, { y: 14, opacity: 0, duration: .5, stagger: .04, ease: 'power2.out', scrollTrigger: once('.bx-swatches', 'top 88%') });
}

/** 04: the line draws as you scroll; the figures count up; the bars grow */
function analytics() {
    const sec = $('[data-bx-analytics]');
    if (!sec) return;
    const card = $('.bx-card', sec), draw = $('.draw', sec), area = $('.area', sec);
    const len = draw.getTotalLength();
    gsap.set(draw, { strokeDasharray: len, strokeDashoffset: len });
    gsap.set(area, { opacity: 0 });
    gsap.to(draw, { strokeDashoffset: 0, ease: 'none', scrollTrigger: { trigger: card, start: 'top 78%', end: 'bottom 52%', scrub: .5 } });
    gsap.to(area, { opacity: 1, ease: 'none', scrollTrigger: { trigger: card, start: 'top 70%', end: 'bottom 45%', scrub: .5 } });
    $$('[data-metric]', sec).forEach((el) => {
        const end = parseFloat(el.dataset.metric), suffix = el.dataset.suffix || '', o = { v: 0 };
        el.textContent = '0' + suffix;
        gsap.to(o, { v: end, duration: 1.4, ease: 'power2.out', onUpdate: () => { el.textContent = Math.round(o.v).toLocaleString() + suffix; }, scrollTrigger: once(card, 'top 80%') });
    });
    gsap.from($$('.bx-top li', sec), { x: -18, opacity: 0, duration: .55, stagger: .1, ease: 'power2.out', scrollTrigger: once('.bx-top', 'top 88%') });
    gsap.from($$('.bx-top i', sec), { scaleX: 0, transformOrigin: 'left center', duration: .9, stagger: .1, ease: 'power3.out', scrollTrigger: once('.bx-top', 'top 88%') });
}

/** 05: lines run from the page to the places it goes, with small packets travelling along them */
function share() {
    const sec = $('[data-bx-share]');
    if (!sec) return;
    const paths = $$('.links path', sec), pkts = $$('.pkt', sec);
    paths.forEach((p) => { const l = p.getTotalLength(); gsap.set(p, { strokeDasharray: l, strokeDashoffset: l }); });
    gsap.set(pkts, { opacity: 0 });
    const tl = gsap.timeline({ scrollTrigger: once('.bx-net', 'top 72%') });
    tl.from('.bx-core', { scale: .6, opacity: 0, duration: .6, ease: 'back.out(1.8)' })
        .to(paths, { strokeDashoffset: 0, duration: .9, stagger: .08, ease: 'power2.inOut' }, '-=.2')
        .from($$('.bx-dest', sec), { opacity: 0, scale: .8, duration: .4, stagger: .08 }, '-=.9')
        .set(pkts, { opacity: 1 });
    const runs = pkts.map((pkt, i) => {
        const path = paths[i * 2 % paths.length], len = path.getTotalLength(), o = { t: 0 };
        return gsap.to(o, { t: 1, duration: 2.2 + i * .5, repeat: -1, ease: 'power1.inOut', paused: true, delay: i * .6,
            onUpdate: () => { const pt = path.getPointAtLength(len * o.t); pkt.setAttribute('cx', pt.x); pkt.setAttribute('cy', pt.y); } });
    });
    ScrollTrigger.create({ trigger: '.bx-net', start: 'top 80%', end: 'bottom 10%', onToggle: (self) => runs.forEach((r) => (self.isActive ? r.play() : r.pause())) });
}

/** 06: the gate asks, the answer is given, the page opens; it plays again while it is in view */
function gates() {
    $$('.bx-gate').forEach((gate) => {
        const page = $('.bx-gate-page', gate), box = $('.bx-gate-box', gate), go = $('.bx-go', gate), dots = $('[data-gate-dots]', gate), shackle = $('.shackle', gate);
        const full = dots ? dots.textContent : '', o = { n: 0 };
        const tl = gsap.timeline({ paused: true, repeat: -1, repeatDelay: 1.2, defaults: { ease: 'power2.out' } });
        tl.set(page, { filter: 'blur(7px)' }).set(box, { autoAlpha: 1, scale: 1 }).set(go, { scale: 1 })
            .call(() => { if (dots) dots.textContent = ''; })
            .from(box, { y: 16, autoAlpha: 0, duration: .5 });
        if (dots) tl.to(o, { n: full.length, duration: 1, ease: 'none', onUpdate: () => { dots.textContent = full.slice(0, Math.round(o.n)); } });
        else tl.to({}, { duration: .8 });
        tl.to(go, { scale: .93, duration: .12, yoyo: true, repeat: 1 }, '+=.2');
        if (shackle) tl.to(shackle, { y: -7, duration: .25, ease: 'back.out(3)' }, '<');
        tl.to(box, { autoAlpha: 0, scale: .92, duration: .4 }, '+=.15').to(page, { filter: 'blur(0px)', duration: .5 }, '<').to({}, { duration: 1.4 });
        ScrollTrigger.create({ trigger: gate, start: 'top 85%', end: 'bottom 10%', onToggle: (self) => (self.isActive ? tl.play() : tl.pause()) });
    });
}

/** 07: the CSS is typed as you scroll, and the phone follows each line */
function style() {
    const sec = $('[data-bx-style]');
    if (!sec) return;
    const code = $('[data-style-code]', sec), phone = $('#style-phone', sec), full = code.textContent, lines = full.split('\n');
    const ends = []; let acc = 0;
    lines.forEach((l) => { acc += l.length + 1; ends.push(acc); });
    const rules = [
        () => phone.style.setProperty('--ph-font', '"Instrument Serif", Georgia, serif'),
        () => phone.style.setProperty('--ph-bg', '#0f172a'),
        () => phone.style.setProperty('--ph-r', '999px'),
        () => { phone.style.setProperty('--ph-l', '#a78bfa'); phone.style.setProperty('--ph-lt', '#0f172a'); phone.style.setProperty('--ph-t', '#ffffff'); phone.style.setProperty('--ph-m', '#c7d2fe'); },
    ];
    const reset = ['--ph-font', '--ph-bg', '--ph-r', '--ph-l', '--ph-lt', '--ph-t', '--ph-m'];
    let shown = -1;
    const o = { n: 0 };
    const show = (n) => {
        code.textContent = full.slice(0, n);
        const done = ends.filter((e) => n >= e - 1).length;
        if (done === shown) return;
        shown = done;
        reset.forEach((r) => phone.style.removeProperty(r));
        for (let i = 0; i < done; i++) rules[i]?.();
    };
    show(0);
    gsap.to(o, { n: full.length, ease: 'none', onUpdate: () => show(Math.round(o.n)), scrollTrigger: { trigger: sec, start: 'top 62%', end: 'bottom 70%', scrub: .6 } });
}

/** 08: the command types itself, then the output prints line by line */
function install() {
    const term = $('.bx-term');
    if (!term) return;
    const code = $('code', term), out = $$('[data-out] span', term), btn = $('[data-copy-text]', term), full = code.textContent, o = { n: 0 };
    if (btn) btn.dataset.copyText = full;
    code.textContent = '';
    gsap.set(out, { opacity: 0 });
    gsap.timeline({ scrollTrigger: once(term, 'top 80%') })
        .to(o, { n: full.length, duration: full.length * .03, ease: 'none', onUpdate: () => { code.textContent = full.slice(0, Math.round(o.n)); } })
        .to(out, { opacity: 1, duration: .05, stagger: .5, ease: 'none' }, '+=.3');
}

function faq() {
    $$('[data-bx-faq] .bx-cell').forEach((c, i) => gsap.from(c, { y: 34, opacity: 0, duration: .7, ease: 'power3.out', delay: (i % 2) * .08, scrollTrigger: once(c, 'top 92%') }));
}

function family() {
    gsap.from($$('[data-bx-family] a'), { y: 20, opacity: 0, scale: .96, duration: .55, stagger: .08, ease: 'back.out(1.5)', scrollTrigger: once('[data-bx-family]', 'top 90%') });
}

if (!reduced && document.querySelector('.bio-home')) start();
