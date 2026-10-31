<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('every dedicated page is served on BioHop with a back link home', function (string $path, string $text) {
    $html = $this->get($path)->assertOk()->getContent();

    expect($html)->toContain($text)
        ->toContain('Back to BioHop')
        ->toContain('href="'.route('home').'"');
})->with([
    ['/features', '20+ themes'],
    ['/about', 'About BioHop'],
    ['/help', 'Make your page'],
    ['/faq', 'Is it really free?'],
    ['/contact', 'https://github.com/navi-crwn/BioHop/issues/new/choose'],
    ['/terms', 'hel.ink/terms'],
    ['/privacy', 'keyed hash'],
]);

test('the status page answers live and is never cached', function () {
    $this->get('/status')->assertOk()->assertSee('BioHop is up.')->assertHeader('Cache-Control', 'no-store, private');
});

test('the header and footer link to BioHop pages, the hub only for the account', function () {
    config()->set('hop.hub_url', 'https://hub.example.test');
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain(route('pages.features'))
        ->toContain(route('pages.privacy'))
        ->toContain(route('pages.contact'))
        ->toContain('MIT License')
        ->not->toContain('All rights reserved');
});
