<?php

test('BioHop pages load the HOP family fonts from hel.ink, not a font CDN', function () {
    foreach (['/', '/report-abuse'] as $path) {
        $html = $this->get($path)->assertOk()->getContent();

        expect($html)->toContain('https://hel.ink/hop-assets/v1/fonts.css')
            ->toContain('integrity="sha384-')
            ->not->toContain('fonts.bunny.net');
    }
});

test('the header shows the official purple BioHop mark, not the old leaf', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('/brand/b10b1000/favicon.svg')
        ->not->toContain('M5 19c0-8 5-13 14-14-1 9-6 14-14 14Z');
});

test('the HOP family section lists the other apps from the shared manifest', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('https://hel.ink/hop-assets/v1/apps/linkhop/mark.svg')
        ->toContain('https://hel.ink/hop-assets/v1/apps/pixelhop/mark.svg')
        ->toContain('https://hel.ink/hop-assets/v1/apps/notehop/mark.svg')
        ->not->toContain('https://hel.ink/hop-assets/v1/apps/biohop/mark.svg');
});

test('the CSP allows the shared assets on hel.ink', function () {
    $csp = $this->get('/')->headers->get('Content-Security-Policy');

    foreach (['script-src', 'style-src', 'font-src'] as $directive) {
        expect($csp)->toMatch("/{$directive} [^;]*https:\\/\\/hel\\.ink/");
    }
});
