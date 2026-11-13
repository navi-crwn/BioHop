<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Symfony\Component\HttpFoundation\Cookie;

// The family preference cookies (KONTRAK-PREFERENSI): hop_lang is shared by every HOP app on .hel.ink, stays on the host
// elsewhere, and is plain text; hop_theme is written by the page head script (resources/js/hop-prefs.js, tests/js).

function familyCookie($response, string $name, ?string $domain = null): ?Cookie
{
    foreach ($response->headers->getCookies() as $cookie) {
        if ($cookie->getName() === $name && $cookie->getDomain() === $domain) {
            return $cookie;
        }
    }

    return null;
}

test('a picked language is a plain hop_lang cookie for .hel.ink on hel.ink hosts, for one year, readable by scripts', function () {
    foreach (['https://bio.hel.ink/?lang=de', 'https://hel.ink/b/missing?lang=de', 'http://bio.hel.ink/faq?lang=pt'] as $url) {
        $response = $this->get($url);
        $cookie = familyCookie($response, 'hop_lang', '.hel.ink');
        expect($cookie)->not->toBeNull($url);
        expect($cookie->getValue())->toBe(str_ends_with($url, 'pt') ? 'pt-BR' : 'de');
        expect($cookie->getPath())->toBe('/');
        expect($cookie->isSecure())->toBeTrue();
        expect($cookie->isHttpOnly())->toBeFalse();
        expect($cookie->getSameSite())->toBe('lax');
        expect($cookie->getMaxAge())->toBeGreaterThan(31535990)->toBeLessThanOrEqual(31536000);
        expect(familyCookie($response, 'hop_lang'))->toBeNull(); // no host-only copy
    }
});

test('on any other host the language cookie stays on the host', function () {
    foreach (['http://localhost/?lang=ja', 'https://bio.example.com/?lang=ja', 'https://nothel.ink/?lang=ja'] as $url) {
        $response = $this->get($url);
        $cookie = familyCookie($response, 'hop_lang');
        expect($cookie)->not->toBeNull($url)->and($cookie->getValue())->toBe('ja');
        expect(familyCookie($response, 'hop_lang', '.hel.ink'))->toBeNull();
        expect(familyCookie($response, 'hop_pref'))->toBeNull();
    }
});

test('the cookie is read as plain text, so a value written by another app picks the page language', function () {
    $this->withUnencryptedCookie('hop_lang', 'id')->get('/')->assertOk()->assertSee('<html lang="id"', false);
    $this->withUnencryptedCookie('hop_lang', 'zh-CN')->get('/faq')->assertOk()->assertSee('<html lang="zh-CN"', false);
    // ?lang= wins over the cookie
    $this->withUnencryptedCookie('hop_lang', 'id')->get('/?lang=fr')->assertSee('<html lang="fr"', false);
});

test('on a hel.ink host the old host-only cookie moves to .hel.ink once', function () {
    $response = $this->withUnencryptedCookie('hop_lang', 'de')->get('https://bio.hel.ink/');
    $expired = familyCookie($response, 'hop_lang');
    expect($expired)->not->toBeNull()->and($expired->isCleared())->toBeTrue();
    expect(familyCookie($response, 'hop_lang', '.hel.ink')->getValue())->toBe('de');
    $marker = familyCookie($response, 'hop_pref');
    expect($marker->getValue())->toBe('2')->and($marker->isHttpOnly())->toBeFalse()->and($marker->getMaxAge())->toBeGreaterThan(31535990);
    // plain text in the browser, not an encrypted value
    expect(implode("\n", $response->headers->all('set-cookie')))->toContain('hop_pref=2;')->toContain('hop_lang=de; expires=');

    // with the marker nothing is moved again
    $again = $this->withUnencryptedCookie('hop_lang', 'de')->withUnencryptedCookie('hop_pref', '2')->get('https://bio.hel.ink/');
    expect(familyCookie($again, 'hop_lang'))->toBeNull()->and(familyCookie($again, 'hop_lang', '.hel.ink'))->toBeNull();

    // a ?lang= in the same request wins over the value carried
    $picked = $this->withUnencryptedCookie('hop_lang', 'de')->get('https://bio.hel.ink/?lang=es');
    expect(familyCookie($picked, 'hop_lang', '.hel.ink')->getValue())->toBe('es');

    // nothing to move: no cookie at all, so a plain public bio page stays cacheable at the edge
    BioPage::create(['user_id' => User::factory()->create()->id, 'slug' => 'plain-page', 'title' => 'Plain', 'is_published' => true]);
    $plain = $this->get('https://hel.ink/b/plain-page')->assertOk();
    expect($plain->headers->getCookies())->toBe([]);
});

test('no migration and no domain cookie off the hel.ink hosts', function () {
    $response = $this->withUnencryptedCookie('hop_lang', 'de')->get('http://localhost/');
    foreach ($response->headers->getCookies() as $cookie) {
        expect($cookie->getDomain())->toBeNull();
    }
    expect(familyCookie($response, 'hop_pref'))->toBeNull();
});

test('every page with a theme toggle reads hop_theme before first paint, and every toggle writes it', function () {
    foreach (['/', '/faq', '/changelog'] as $path) {
        $html = $this->get($path)->assertOk()->getContent();
        expect(substr($html, 0, strpos($html, '</head>')))->toContain('hopPrefs')->toContain("cookie('hop_theme')");
    }
    $html = $this->actingAs(User::factory()->create())->get('/bio')->assertOk()->getContent();
    expect(substr($html, 0, strpos($html, '</head>')))->toContain("cookie('hop_theme')");
    expect($html)->toContain('window.hopPrefs.setTheme(isDark)');
    expect(file_get_contents(resource_path('views/partials/site-header.blade.php')))->toContain('hopPrefs.setTheme(d)');
    expect(file_get_contents(resource_path('js/inline-actions.js')))->toContain('window.hopPrefs.setTheme(dark)');
    // the public bio pages keep their own theme
    expect(file_get_contents(base_path('packages/bio/resources/views/show.blade.php')))->not->toContain('hop-prefs')->not->toContain('hop_theme');
});

// A browser holding this host's old host-only cookie and the newer .hel.ink one sends both, oldest first; PHP keeps the
// first. The last one in the raw header is the newer family choice and counts (contract amendment after LinkHop's audit).
function withRawCookies($test, string $url, string $header)
{
    return $test->call('GET', $url, [], [], [], ['HTTP_COOKIE' => $header, 'HTTPS' => str_starts_with($url, 'https') ? 'on' : 'off']);
}

test('when hop_lang comes twice the last one counts, for the page and for the clean-up', function () {
    $response = withRawCookies($this, 'https://bio.hel.ink/', 'hop_lang=en; hop_lang=ja');
    $response->assertOk()->assertSee('<html lang="ja"', false);
    expect(familyCookie($response, 'hop_lang', '.hel.ink')->getValue())->toBe('ja');
    expect(familyCookie($response, 'hop_lang')->isCleared())->toBeTrue();
    expect(familyCookie($response, 'hop_pref')->getValue())->toBe('2');

    // a single cookie: as before
    $single = withRawCookies($this, 'https://bio.hel.ink/', 'hop_lang=de');
    $single->assertSee('<html lang="de"', false);
    expect(familyCookie($single, 'hop_lang', '.hel.ink')->getValue())->toBe('de');

    // ?lang= in the same request still wins
    $picked = withRawCookies($this, 'https://bio.hel.ink/?lang=fr', 'hop_lang=en; hop_lang=ja');
    $picked->assertSee('<html lang="fr"', false);
    expect(familyCookie($picked, 'hop_lang', '.hel.ink')->getValue())->toBe('fr');

    // off the family hosts the last one picks the page too, and nothing is migrated
    $local = withRawCookies($this, 'http://localhost/', 'hop_lang=en; hop_lang=es');
    $local->assertSee('<html lang="es"', false);
    expect(familyCookie($local, 'hop_pref'))->toBeNull();
});

test('a malformed Cookie header does not break the page language', function () {
    foreach (['hop_lang', 'hop_lang=', ';;; =x; hop_lang', 'hop_lang=xx', 'hop_lang==de', 'other=1; hop_lang=%7Bbad'] as $header) {
        withRawCookies($this, 'http://localhost/', $header)->assertOk()->assertSee('<html lang="en"', false);
    }
    withRawCookies($this, 'http://localhost/', 'hop_lang=xx; hop_lang=id')->assertSee('<html lang="id"', false);
});
