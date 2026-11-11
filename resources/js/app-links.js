// App block on a phone: when the block's address belongs to a known app (the server checked it with hop/safety
// DeepLinks and printed the app's own address for each system into data-ios / data-android), a tap opens the app.
// The link itself is the normal tracked https address, so without this script and on computers nothing changes.
// The address is only ever one the server printed: nothing is built here from page content.
const ua = navigator.userAgent || '';
const ios = /iPhone|iPad|iPod/.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);
const android = /Android/.test(ua);

function addressFor(el) {
    return (ios ? el.dataset.ios : android ? el.dataset.android : '') || '';
}

if (ios || android) {
    document.addEventListener('click', (e) => {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const a = e.target.closest && e.target.closest('a[data-app]');
        const to = a && addressFor(a);
        if (!to) return;
        e.preventDefault();
        // the click is still counted: the tracked address is asked for, its answer is not followed
        try { fetch(a.href, { redirect: 'manual', keepalive: true, credentials: 'omit' }).catch(() => {}); } catch (err) { /* counting is best effort */ }
        location.href = to;
    });
}
