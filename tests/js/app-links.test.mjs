// Run: node --test tests/js/app-links.test.mjs   (Pest runs it too: tests/Feature/AppDeepLinkTest.php)
import test from 'node:test';
import assert from 'node:assert/strict';
import { openApp, platform, FALLBACK_MS } from '../../resources/js/app-links.js';

const data = { ios: 'youtube://www.youtube.com/watch?v=abc123', android: 'intent://www.youtube.com/watch?v=abc123#Intent;scheme=https;end', fallback: 'https://www.youtube.com/watch?v=abc123', href: 'https://hel.ink/b/x/click/abc' };

// a fake page: records counts and navigations, runs the timer by hand, lets the test say the page was left
function fake(p) {
    const log = { counted: [], went: [], leave: null, timer: null, cancelled: false };
    const env = {
        platform: p,
        count: (u) => log.counted.push(u),
        go: (u) => log.went.push(u),
        after: (fn, ms) => { log.timer = { fn, ms }; return () => { log.cancelled = true; log.timer = null; }; },
        onLeave: (fn) => { log.leave = fn; },
    };
    return { env, log };
}

test('the platform comes from the user agent; a desktop gets none', () => {
    assert.equal(platform('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)', 5), 'ios');
    assert.equal(platform('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', 5), 'ios'); // iPadOS asks for the desktop site
    assert.equal(platform('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', 0), '');
    assert.equal(platform('Mozilla/5.0 (Linux; Android 14; Pixel)', 5), 'android');
    assert.equal(platform('Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 0), '');
});

test('iPhone, left the page for the app: no fallback, and coming back does not move the visitor', () => {
    const { env, log } = fake('ios');
    assert.equal(openApp(env, data), true);
    assert.deepEqual(log.counted, [data.href]);
    assert.deepEqual(log.went, [data.ios]);
    assert.equal(log.timer.ms, FALLBACK_MS);
    log.leave(); // visibilitychange / pagehide: the app opened
    assert.equal(log.cancelled, true);
    assert.equal(log.timer, null);
    log.leave(); // a second event changes nothing
    assert.deepEqual(log.went, [data.ios]);
    assert.deepEqual(log.counted, [data.href]);
});

test('iPhone, the app is not there: the block\'s https address opens once, and the tap was counted once', () => {
    const { env, log } = fake('ios');
    openApp(env, data);
    const t = log.timer;
    t.fn();
    assert.deepEqual(log.went, [data.ios, data.fallback]);
    t.fn(); // a timer firing twice still goes once
    assert.deepEqual(log.went, [data.ios, data.fallback]);
    log.leave(); // leaving after the fallback went does not add anything
    assert.deepEqual(log.counted, [data.href]);
    assert.ok(!log.went.includes(data.href), 'the fallback is the block address, not the counted link');
});

test('Android: the intent address is opened as it is, with no timer and no second navigation', () => {
    const { env, log } = fake('android');
    assert.equal(openApp(env, data), true);
    assert.deepEqual(log.went, [data.android]);
    assert.deepEqual(log.counted, [data.href]);
    assert.equal(log.timer, null);
    assert.equal(log.leave, null);
});

test('no address for the platform, or no platform: the plain link is left alone', () => {
    for (const [p, d] of [['ios', { ...data, ios: '' }], ['android', { ...data, android: undefined }], ['', data]]) {
        const { env, log } = fake(p);
        assert.equal(openApp(env, d), false);
        assert.deepEqual([log.counted, log.went], [[], []]);
    }
});

test('an iPhone block without a usable https fallback still opens the app but never navigates elsewhere', () => {
    for (const fallback of ['', 'javascript:alert(1)', 'http://example.com', undefined]) {
        const { env, log } = fake('ios');
        openApp(env, { ...data, fallback });
        assert.equal(log.timer, null);
        assert.deepEqual(log.went, [data.ios]);
    }
});
