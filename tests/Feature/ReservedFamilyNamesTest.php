<?php

use App\Support\ReservedSlugs;

/*
| H-17: every app of the family manifest, and PayHop by name, is a word nobody can take as a bio slug or a username.
| Lists are checked against the manifest, not a fixed count, so the next app that joins fails here until it is added.
*/

test('every app in the family manifest is a reserved bio slug and a reserved username', function () {
    $slugs = collect(\Hop\Ui\Assets::manifest()['apps'])->pluck('slug')->reject('hub');
    expect($slugs)->toContain('payhop');

    foreach ($slugs->push('pay') as $slug) {
        expect(ReservedSlugs::isReserved($slug))->toBeTrue("{$slug} is not a reserved bio slug");
        expect(in_array($slug, config('hop.reserved_usernames'), true))->toBeTrue("{$slug} is not a reserved username");
    }
    expect(config('hop.reserved_username_prefixes'))->toContain('payhop-');
});

test('payhop and pay cannot be claimed as a bio slug, whatever the case', function () {
    foreach (['payhop', 'PayHop', 'pay', 'PAY'] as $slug) {
        expect(ReservedSlugs::isReserved($slug))->toBeTrue($slug);
        $this->getJson('/check-slug-public?slug='.$slug)->assertOk()->assertJson(['available' => false]);
    }
});
