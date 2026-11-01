<?php

test('the sample phones on the home page are decoration: they never scroll inside, so the header is never pushed out of view', function () {
    $css = file_get_contents(base_path('resources/css/home.css'));
    $phone = file_get_contents(base_path('resources/views/components/bx-phone.blade.php'));
    expect($css)->toMatch('/\.bx-phone-in \{[^}]*overflow: hidden/')->and($phone)->not->toContain('data-lenis-prevent');
});

test('the closing paragraph of the home page stays centred', function () {
    $css = file_get_contents(base_path('resources/css/home.css'));
    expect($css)->toContain('.bx-final .h-sub.h-sub { margin-inline: auto; }');
});

test('the home 3D engine animates every app mark in the family row, not only the most visible one', function () {
    $js = file_get_contents(public_path('hop-home/hop-hero3d.js'));
    expect($js)->toContain('.slice(0, 4)')->not->toContain('.sort(function (a, b) { return b.ratio - a.ratio; })[0]');
});

test('the home page versions its engine files by content, so an edited file is not served from a year-old cache', function () {
    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->toMatch('#hop-home/hop-hero3d\.js\?v=[0-9a-f]{8}#')->and($html)->toMatch('#hop-home/hop-home\.css\?v=[0-9a-f]{8}#');
});
