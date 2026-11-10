<?php

/*
| K-8: the changelog, privacy and terms pages (and every other page on the shared info layout) carry an og:image that
| exists in the app, plus a title and a card type, so a shared link shows a preview.
*/

test('the info pages carry og:image, og:title and a large card', function (string $path) {
    $html = $this->get($path)->assertOk()->getContent();

    preg_match('~<meta property="og:image" content="([^"]+)">~', $html, $image);
    expect($image[1] ?? '')->toEndWith('/images/brand/og.png')
        ->and($html)->toContain('<meta property="og:title"')->toContain('<meta name="twitter:card" content="summary_large_image">');
    expect(is_file(public_path('images/brand/og.png')))->toBeTrue();
})->with(['/changelog', '/privacy', '/terms', '/faq']);
