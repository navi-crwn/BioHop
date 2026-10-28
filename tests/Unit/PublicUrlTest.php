<?php

use App\Support\ReservedSlugs;

test('public_url builds addresses on the public host, not the dashboard', function () {
    config(['hop.public_url' => 'https://hel.ink', 'app.url' => 'https://bio.hel.ink']);
    expect(public_url('b/abc'))->toBe('https://hel.ink/b/abc')
        ->and(public_url('/b/abc/qr'))->toBe('https://hel.ink/b/abc/qr')
        ->and(public_url())->toBe('https://hel.ink')
        ->and(public_host())->toBe('hel.ink');
});

test('the router prefixes are always reserved as bio slugs', function () {
    expect(ReservedSlugs::isReserved('b'))->toBeTrue()
        ->and(ReservedSlugs::isReserved('L'))->toBeTrue()
        ->and(ReservedSlugs::isReserved('Admin'))->toBeTrue()
        ->and(ReservedSlugs::isReserved('hel'))->toBeFalse()
        ->and(ReservedSlugs::isReserved('myname'))->toBeFalse();
});
