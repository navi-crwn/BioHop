<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;

// HOP does not call third-party hosts from its own pages: libraries are bundled, fonts and flags are local.
// (A page owner's own optional, consent-gated pixels are the exception and are named in the policy on purpose.)
const BIO_THIRD_PARTY = ['cdn.jsdelivr.net', 'unpkg.com', 'cdn.tailwindcss.com', 'flagcdn.com', 'fonts.googleapis.com', 'fonts.gstatic.com', 'fonts.bunny.net', 'cdn.simpleicons.org', 'cdnjs.cloudflare.com'];

test('a public page and the home contact no CDN, font or icon host', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'nothird', 'title' => 'No third party', 'is_published' => true]);
    foreach (['/b/nothird', '/'] as $path) {
        $response = $this->get($path)->assertOk();
        $html = $response->getContent();
        $csp = (string) $response->headers->get('Content-Security-Policy');
        foreach (BIO_THIRD_PARTY as $host) {
            expect($html)->not->toContain($host)->and($csp)->not->toContain($host);
        }
    }
});

test('a country without a flag file shows its code, never a picture from another host', function () {
    expect(country_flag('QQ'))->toContain('QQ')->not->toContain('<img')->not->toContain('flagcdn');
    expect(country_flag('ID'))->toContain('images/flags/id.svg');
});

test('the 500 page needs no CDN script', function () {
    $html = view('errors.500', ['exception' => new Exception('x')])->render();
    foreach (BIO_THIRD_PARTY as $host) {
        expect($html)->not->toContain($host);
    }
});

test('the editor script builds no address on a third-party icon host', function () {
    $js = file_get_contents(base_path('resources/js/editor.js'));
    expect($js)->not->toContain('cdn.simpleicons.org');
});
