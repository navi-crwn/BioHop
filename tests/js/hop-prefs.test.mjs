// Run: node --test tests/js/hop-prefs.test.mjs. The head script that reads and writes the family theme cookie.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/js/hop-prefs.js', import.meta.url), 'utf8');

// A browser with a cookie jar that keeps what was written (name=value only), the old localStorage key and the system theme.
function run({ host = 'localhost', cookie = '', stored = null, systemDark = false, blocked = false } = {}) {
    const jar = new Map(cookie ? cookie.split('; ').map((p) => p.split('=')) : []);
    const writes = [];
    const classes = new Set();
    const store = new Map(stored ? [['helink-theme', stored]] : []);
    const document = {
        get cookie() { return [...jar].map(([k, v]) => k + '=' + v).join('; '); },
        set cookie(raw) { writes.push(raw); const [pair] = raw.split('; '); const [k, v] = pair.split('='); jar.set(k, v); },
        documentElement: { classList: { add: (c) => classes.add(c) } },
    };
    const localStorage = {
        getItem: (k) => { if (blocked) throw new Error('blocked'); return store.has(k) ? store.get(k) : null; },
        setItem: (k, v) => { if (blocked) throw new Error('blocked'); store.set(k, v); },
    };
    const window = { location: { hostname: host }, localStorage, matchMedia: () => ({ matches: systemDark }) };
    vm.runInNewContext(source, { window, document });
    return { dark: classes.has('dark'), writes, jar, store, prefs: window.hopPrefs };
}

test('the cookie decides the first paint before the old key and the system', () => {
    assert.equal(run({ cookie: 'hop_theme=dark', stored: 'light' }).dark, true);
    assert.equal(run({ cookie: 'hop_theme=light', systemDark: true }).dark, false);
});

test('without the cookie the old key is used once and copied into the cookie', () => {
    const r = run({ stored: 'dark' });
    assert.equal(r.dark, true);
    assert.deepEqual(r.writes, ['hop_theme=dark; Path=/; Max-Age=31536000; SameSite=Lax']);
});

test('no cookie and no old key, or an unknown cookie value, follow the system and write nothing', () => {
    assert.equal(run({ systemDark: true }).dark, true);
    assert.equal(run({ systemDark: false }).dark, false);
    const sys = run({ cookie: 'hop_theme=system', stored: 'light', systemDark: true });
    assert.equal(sys.dark, true);
    assert.deepEqual(sys.writes, []);
});

test('on hel.ink hosts the cookie belongs to .hel.ink and is Secure; elsewhere it stays on the host', () => {
    for (const host of ['hel.ink', 'bio.hel.ink']) {
        const r = run({ host });
        r.prefs.setTheme(true);
        assert.equal(r.writes.at(-1), 'hop_theme=dark; Path=/; Max-Age=31536000; SameSite=Lax; domain=.hel.ink; Secure');
    }
    for (const host of ['localhost', 'bio.example.com', 'nothel.ink']) {
        const r = run({ host });
        r.prefs.setTheme(false);
        assert.equal(r.writes.at(-1), 'hop_theme=light; Path=/; Max-Age=31536000; SameSite=Lax');
    }
});

test('a toggle writes the cookie and still writes the old key; blocked storage keeps the cookie', () => {
    const r = run();
    r.prefs.setTheme(true);
    assert.equal(r.jar.get('hop_theme'), 'dark');
    assert.equal(r.store.get('helink-theme'), 'dark');
    const b = run({ blocked: true, systemDark: false });
    b.prefs.setTheme(true);
    assert.equal(b.jar.get('hop_theme'), 'dark');
});
