<?php

use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;

// Public pages are served from hel.ink while the app (and URL::forceRootUrl) is bio.hel.ink. Every form on such a page
// must post to the host the page came from (the CSRF token and the session belong to it) and share links must name the
// public host, never the app host.

test('the password gate and the age check post to the host that served them', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'gate-pw', 'title' => 'Gate', 'is_published' => true, 'password_enabled' => true, 'password' => 'secret']);
    $html = $this->get('/b/gate-pw')->assertOk()->getContent();
    expect($html)->toContain('<form method="POST" action="/b/gate-pw"')->not->toContain('action="http');
});

test('the PIN form of a link and its way back use paths, not app-host addresses', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'forms', 'title' => 'Forms', 'is_published' => true]);
    $pinned = BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Secret', 'url' => 'https://example.com/secret', 'position' => 1, 'settings' => ['pin' => '2468']]);

    $pin = $this->get(route('bio.public.click', $pinned->public_id, false))->assertOk()->getContent();
    expect($pin)->toContain('action="/b/click/'.$pinned->public_id.'/pin"')->not->toContain('action="http');
});

test('share links and the page tabs name the public host', function () {
    config(['hop.public_url' => 'https://hel.test']);
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'share', 'title' => 'Share', 'is_published' => true]);
    $html = $this->get('/b/share')->assertOk()->getContent();
    expect($html)->toContain('value="https://hel.test/b/share"')->and($html)->toContain('u=https%3A%2F%2Fhel.test%2Fb%2Fshare');
});
