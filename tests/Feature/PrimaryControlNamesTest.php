<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;

// At phone width these controls show only an icon: their text is hidden, so the name has to come from aria-label.

test('the brand link in the public header has a name when its text is hidden', function (string $path) {
    $html = (string) $this->get($path)->assertOk()->getContent();
    expect($html)->toMatch('~<a href="[^"]*"[^>]*aria-label="BioHop by hel\.ink"[^>]*class="flex shrink-0 items-center gap-2\.5~');
})->with(['/', '/about', '/faq', '/changelog', '/status']);

test('the new bio button has a name when its text is hidden', function (string $path) {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'namedemo', 'title' => 'T']);
    $html = (string) $this->actingAs($user)->get($path)->assertOk()->getContent();
    expect($html)->toMatch('~<a href="[^"]*/bio/create"[^>]*class="btn-primary"[^>]*aria-label="New bio"~');
})->with(['/bio', '/bio/import', '/bio/namedemo/analytics', '/bio/namedemo/edit', '/bio/create', '/settings']);
