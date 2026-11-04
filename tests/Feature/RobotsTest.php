<?php

// robots.txt: keep sign-in-only areas out of search, point crawlers at the sitemap of this install.

test('robots.txt is answered by the app, so the sitemap line carries this install\'s address', function () {
    expect(file_exists(public_path('robots.txt')))->toBeFalse();
    config(['app.url' => 'https://staging.example.test']);

    $res = $this->get('/robots.txt')->assertOk();
    expect($res->headers->get('Content-Type'))->toContain('text/plain');
    $body = $res->getContent();
    expect($body)->toContain("User-agent: *\n")
        ->toContain('Sitemap: '.route('sitemap'))
        ->toContain("Disallow: /bio/\n")
        ->toContain("Disallow: /settings\n")
        ->toContain("Disallow: /admin\n")
        ->toContain("Disallow: /auth/\n")
        ->toContain("Disallow: /internal/\n")
        ->not->toMatch('#^Disallow:\s*$#m')
        ->not->toContain('Disallow: /b/');
});
