<?php

test('the public page asks for its own small script and stylesheet, not the whole app bundle', function () {
    $view = file_get_contents(base_path('packages/bio/resources/views/show.blade.php'));
    expect($view)->toContain("@vite(['resources/css/public.css', 'resources/js/public.js'])")
        ->not->toContain("@vite(['resources/css/app.css'");
});

test('the built public stylesheet stays small', function () {
    $manifestPath = public_path('build/manifest.json');
    if (! is_file($manifestPath)) {
        $this->markTestSkipped('assets are not built here');
    }
    $manifest = json_decode(file_get_contents($manifestPath), true);
    $css = public_path('build/'.$manifest['resources/css/public.css']['file']);
    // the whole app stylesheet is about 180 KB; the public one only carries what public pages use
    expect(filesize($css))->toBeLessThan(60_000);
});
