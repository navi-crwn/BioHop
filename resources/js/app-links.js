// App block on a phone: when the block's address belongs to a known app (the server checked it with hop/safety
// DeepLinks and printed the app's own address for each system into data-ios / data-android, and the block's own https
// address into data-fallback), a tap opens the app. The link itself is the normal tracked https address, so without
// this script and on computers nothing changes. Addresses are only ever ones the server printed.
//
// Android: the library's intent:// address carries its own web fallback, so it is simply opened.
// iPhone and iPad: the address is a bare scheme (youtube://, whatsapp://, ...) with no way back, so after opening it the
// page watches whether it was left (visibilitychange / pagehide). If it was not within FALLBACK_MS the app is not there
// and the visitor goes to the block's https address. A visitor who left for the app and comes back is not sent
// anywhere. The tap is counted once: the tracked address is asked for when the tap happens, the fallback address is the
// block's own address and is not counted again.
export const FALLBACK_MS = 1500;

export function platform(ua, touchPoints) {
    if (/iPhone|iPad|iPod/.test(ua) || (/Macintosh/.test(ua) && touchPoints > 1)) return 'ios';
    return /Android/.test(ua) ? 'android' : '';
}

/**
 * Opens the app for one tapped block. `env` = { platform, count(url), go(url), after(fn, ms) -> cancel, onLeave(fn) }
 * (the page passes real ones; the tests pass fakes). `data` = the block's data attributes { ios, android, fallback, href }.
 * Returns false when the tap should be left alone (the plain link works).
 */
export function openApp(env, data) {
    const to = (env.platform === 'ios' ? data.ios : env.platform === 'android' ? data.android : '') || '';
    if (!to) return false;
    env.count(data.href);
    if (env.platform === 'ios' && /^https:\/\//i.test(data.fallback || '')) {
        let left = false;
        let cancel = () => {};
        const gone = () => { left = true; cancel(); };
        env.onLeave(gone);
        cancel = env.after(() => { if (!left) { left = true; env.go(data.fallback); } }, FALLBACK_MS);
    }
    env.go(to);
    return true;
}

if (typeof document !== 'undefined' && typeof navigator !== 'undefined') {
    const p = platform(navigator.userAgent || '', navigator.maxTouchPoints || 0);
    if (p) {
        document.addEventListener('click', (e) => {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            const a = e.target.closest && e.target.closest('a[data-app]');
            if (!a) return;
            const env = {
                platform: p,
                // the tracked address is asked for, its answer is not followed
                count: (url) => { try { fetch(url, { redirect: 'manual', keepalive: true, credentials: 'omit' }).catch(() => {}); } catch (err) { /* counting is best effort */ } },
                go: (url) => { location.href = url; },
                after: (fn, ms) => { const t = setTimeout(fn, ms); return () => clearTimeout(t); },
                onLeave: (fn) => {
                    document.addEventListener('visibilitychange', () => { if (document.hidden) fn(); });
                    window.addEventListener('pagehide', fn);
                },
            };
            if (openApp(env, { ios: a.dataset.ios, android: a.dataset.android, fallback: a.dataset.fallback, href: a.href })) e.preventDefault();
        });
    }
}
