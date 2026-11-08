<?php

test('BioHop pages load the HOP family fonts from hel.ink, not a font CDN', function () {
    foreach (['/', '/report-abuse'] as $path) {
        $html = $this->get($path)->assertOk()->getContent();

        // hop/ui takes the version from the family manifest (v3 since FileHop joined); any version is fine here.
        expect($html)->toMatch('#https://hel.ink/hop-assets/v\d+/fonts-amnesia.css#')
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

    foreach (['linkhop', 'pixelhop', 'notehop'] as $app) {
        expect($html)->toMatch("#https://hel\.ink/hop-assets/v\d+/apps/{$app}/mark\.svg#");
    }
    $footer = substr($html, strpos($html, '<footer'));
    expect($footer)->not->toMatch('#https://hel.ink/hop-assets/v\d+/apps/biohop/mark.svg#');
    expect($html)->toMatch('/href="https:\/\/bio\.hel\.ink"\s+aria-current="true"/');
});

test('the CSP allows the shared assets on hel.ink', function () {
    $csp = $this->get('/')->headers->get('Content-Security-Policy');

    foreach (['script-src', 'style-src', 'font-src'] as $directive) {
        expect($csp)->toMatch("/{$directive} [^;]*https:\\/\\/hel\\.ink/");
    }
});
