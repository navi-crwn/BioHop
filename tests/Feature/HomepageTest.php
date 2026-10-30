<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the home page speaks English by default and ignores Accept-Language', function () {
    $this->withHeader('Accept-Language', 'id-ID,id;q=0.9')->get('/')
        ->assertOk()->assertSee('Claim my page')->assertSee('lang="en"', false);
});

test('?lang= switches the language and remembers it in a cookie', function () {
    $this->get('/?lang=id')->assertOk()->assertSee('Ambil halamanku')->assertCookie('hop_lang', 'id', false);
    $this->withUnencryptedCookie('hop_lang', 'ja')->get('/')->assertOk()->assertSee('ページを取得');
});

test('Arabic is right to left', function () {
    $this->get('/?lang=ar')->assertOk()->assertSee('dir="rtl"', false)->assertSee('احجز صفحتي');
});

test('an unknown language falls back to English', function () {
    $this->get('/?lang=xx')->assertOk()->assertSee('Claim my page')->assertCookieMissing('hop_lang');
});

test('every language file has every home page string', function () {
    $en = json_decode(file_get_contents(lang_path('id.json')), true);
    foreach (['zh', 'es', 'ar', 'pt', 'fr', 'ja'] as $locale) {
        $other = json_decode(file_get_contents(lang_path($locale.'.json')), true);
        expect(array_keys($other))->toEqual(array_keys($en));
        expect(array_filter($other, fn ($v) => trim((string) $v) === ''))->toBeEmpty();
    }
});

test('the theme gallery shows the editor presets and the page uses no emoji', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('Galaxy')->toContain('Cherry Blossom')->toContain('id="themes"')
        ->toContain('id="i-palette"')
        ->and(preg_match('/[\x{1F300}-\x{1FAFF}]/u', $html))->toBe(0);
});

test('the public slug check answers in the page language and knows reserved names', function () {
    $this->getJson('/check-slug-public?slug=imperium')->assertOk()->assertJson(['available' => false, 'message' => 'This name is reserved.']);
    $this->withCredentials()->withUnencryptedCookie('hop_lang', 'id')->getJson('/check-slug-public?slug=fresh-creator')->assertJson(['available' => true, 'message' => 'Nama ini tersedia!']);
});
