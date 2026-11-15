// The family theme cookie (hop_theme), shared by every HOP app: a theme picked in one dashboard is the theme of the next.
// This file is printed inline in <head> (resources/views/partials/hop-prefs.blade.php), so it runs before first paint.
// Order: cookie hop_theme, then this app's old localStorage key (copied into the cookie once), then the system setting.
// On *.hel.ink the cookie is set for .hel.ink (Secure); elsewhere (localhost, a self-hosted copy) it stays on the host.
// The public bio pages keep their own theme and do not load this file.
(function (w, d) {
    var OLD = 'helink-theme';
    var write = function (name, value) {
        d.cookie = name + '=' + value + '; Path=/; Max-Age=31536000; SameSite=Lax'
            + (/(^|\.)hel\.ink$/.test(w.location.hostname) ? '; domain=.hel.ink; Secure' : '');
    };
    var cookie = function (name) {
        var m = d.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
        return m ? m[1] : null;
    };
    var old = function () {
        try { var v = w.localStorage.getItem(OLD); return v === 'dark' || v === 'light' ? v : null; } catch (e) { return null; }
    };
    // 'dark', 'light', or null (follow the system)
    var theme = function () {
        var c = cookie('hop_theme');
        if (c === 'dark' || c === 'light') return c;
        if (c !== null) return null; // any other value (system) follows the system
        var o = old();
        if (o) write('hop_theme', o);
        return o;
    };
    var setTheme = function (dark) {
        var v = dark ? 'dark' : 'light';
        write('hop_theme', v);
        try { w.localStorage.setItem(OLD, v); } catch (e) { /* storage blocked: the cookie still holds it */ }
    };
    var t = theme();
    if (t === 'dark' || (!t && w.matchMedia && w.matchMedia('(prefers-color-scheme: dark)').matches)) d.documentElement.classList.add('dark');
    // The colour palette (classic | mist | sand), a family setting in the cookie hop_palette, written like hop_theme and only by script.
    // No cookie or any other value is classic, which sets no attribute. Only a signed-in member's screens carry it (the layout
    // marks them with data-member); public pages stay classic.
    var palette = function () {
        var c = cookie('hop_palette');
        try { c = c === null ? null : decodeURIComponent(c); } catch (e) { c = null; }
        return c === 'mist' || c === 'sand' ? c : 'classic';
    };
    var applyPalette = function (p) {
        var el = d.documentElement;
        if (!el.hasAttribute('data-member') || p === 'classic') el.removeAttribute('data-palette');
        else el.setAttribute('data-palette', p);
    };
    var setPalette = function (p) {
        p = p === 'mist' || p === 'sand' ? p : 'classic';
        write('hop_palette', p);
        applyPalette(p);
    };
    applyPalette(palette());
    w.hopPrefs = { theme: theme, setTheme: setTheme, palette: palette, setPalette: setPalette };
})(window, document);
