<?php

use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Illuminate\Support\Facades\URL;

// Public pages are served from hel.ink while the app (and URL::forceRootUrl) is bio.hel.ink. Every form on such a page
// must post to the host the page came from (the CSRF token and the session belong to it) and share links must name the
// public host, never the app host.

test('the password gate and the age check post to the host that served them', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'gate-pw', 'title' => 'Gate', 'is_published' => true, 'password_enabled' => true, 'password' => 'secret']);
    $html = $this->get('/b/gate-pw')->assertOk()->getContent();
    expect($html)->toContain('<form method="POST" action="/b/gate-pw"')->not->toContain('action="http');
});

// The session cookie belongs to one host. A gate that answered "confirmed" with a redirect to the app host (bio.hel.ink)
// stored the confirmation on hel.ink and then showed the gate again on bio.hel.ink: two clicks, and the wrong address.
test('one confirmation of the age gate opens the page on the host the visitor came by', function () {
    URL::forceRootUrl('http://bio.test');
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'gate-age', 'title' => 'Grown-up page', 'is_published' => true, 'content_warning' => 'adult', 'is_adult_content' => true]);

    $gate = $this->get('http://hel.test/b/gate-age?src=qr')->assertOk()->getContent();
    expect($gate)->toContain('action="/b/gate-age?src=qr"');
    $confirmed = $this->post('http://hel.test/b/gate-age?src=qr', ['age_confirm' => 'yes'])->assertStatus(303);
    expect($confirmed->headers->get('Location'))->toBe('/b/gate-age?src=qr');
    $this->get('http://hel.test/b/gate-age?src=qr')->assertOk()->assertSee('Grown-up page')->assertDontSee('name="age_confirm"', false);
});

test('one right password opens the page on the host the visitor came by, a wrong one stays on the gate', function () {
    URL::forceRootUrl('http://bio.test');
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'gate-pass', 'title' => 'Members page', 'is_published' => true, 'password_enabled' => true, 'password' => 'secret']);

    $this->post('http://hel.test/b/gate-pass', ['password' => 'wrong'])->assertOk()->assertSee('Incorrect password');
    $opened = $this->post('http://hel.test/b/gate-pass', ['password' => 'secret'])->assertStatus(303);
    expect($opened->headers->get('Location'))->toBe('/b/gate-pass');
    $this->get('http://hel.test/b/gate-pass')->assertOk()->assertSee('Members page')->assertDontSee('name="password"', false);
});

test('the gates of the shop address post back to it and stay on it', function () {
    URL::forceRootUrl('http://bio.test');
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'gate-shop', 'title' => 'Shop page', 'is_published' => true, 'content_warning' => 'sensitive']);
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'product', 'title' => 'Mug', 'url' => 'https://example.com/mug', 'position' => 1]);

    expect($this->get('http://hel.test/b/shop/gate-shop')->assertOk()->getContent())->toContain('action="/b/shop/gate-shop"');
    $confirmed = $this->post('http://hel.test/b/shop/gate-shop', ['age_confirm' => 'continue'])->assertStatus(303);
    expect($confirmed->headers->get('Location'))->toBe('/b/shop/gate-shop');
    $this->get('http://hel.test/b/shop/gate-shop')->assertOk()->assertSee('Shop page')->assertDontSee('name="age_confirm"', false);
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
