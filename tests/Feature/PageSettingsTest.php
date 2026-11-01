<?php

use App\Models\User;
use Hop\Bio\Models\BioClick;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;

function settingsPage(string $slug, array $extra = []): array
{
    $user = User::factory()->create();
    $page = BioPage::create($extra + ['user_id' => $user->id, 'slug' => $slug, 'title' => 'Settings', 'is_published' => true]);

    return [$user, $page];
}

test('share picture, favicon, tag manager and the footer line reach the public page', function () {
    [$user, $page] = settingsPage('pset');
    $this->actingAs($user)->putJson('/bio/pset', ['og_image' => 'bio-images/share.png', 'favicon' => 'bio-images/fav.png', 'gtm_id' => 'GTM-AB12CD3', 'footer_note' => 'Prices in IDR', 'footer_url' => 'https://example.com/terms'])->assertOk();
    $html = $this->get('/b/pset')->assertOk()->getContent();
    expect($html)->toContain('property="og:image" content="')->toContain('/storage/bio-images/share.png')
        ->toContain('rel="icon" href="')->toContain('/storage/bio-images/fav.png')
        ->toContain('Google Tag Manager')->toContain('GTM-AB12CD3')
        ->toContain('Prices in IDR')->toContain('href="https://example.com/terms"');
    $this->putJson('/bio/pset', ['gtm_id' => 'UA-123'])->assertStatus(422);
    $this->putJson('/bio/pset', ['footer_url' => 'javascript:alert(1)'])->assertStatus(422);
});

test('an unpublished page with a fallback address sends visitors there', function () {
    [$user, $page] = settingsPage('pfall', ['is_published' => false]);
    $this->get('/b/pfall')->assertNotFound();
    $this->actingAs($user)->putJson('/bio/pfall', ['fallback_url' => 'https://example.com/elsewhere'])->assertOk();
    $this->get('/b/pfall')->assertRedirect('https://example.com/elsewhere');
    $this->putJson('/bio/pfall', ['is_published' => true])->assertOk();
    $this->get('/b/pfall')->assertOk()->assertSee('Settings');
});

test('privacy mode stops counting views and clicks', function () {
    [$user, $page] = settingsPage('ppriv', ['privacy_mode' => true]);
    $link = BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Site', 'url' => 'https://example.com/', 'order' => 0, 'is_active' => true]);
    $this->get('/b/ppriv')->assertOk();
    $this->get(route('bio.public.click', $link->public_id))->assertRedirect('https://example.com/');
    expect($page->fresh()->view_count)->toBe(0)->and(BioClick::count())->toBe(0)->and($link->fresh()->click_count)->toBe(0);
    $page->update(['privacy_mode' => false]);
    $this->get('/b/ppriv');
    expect($page->fresh()->view_count)->toBe(1);
});

test('a link with a PIN opens only after the right PIN, once per session', function () {
    [$user, $page] = settingsPage('ppin');
    $this->actingAs($user)->postJson('/bio/ppin/links', ['type' => 'link', 'title' => 'Secret', 'url' => 'https://example.com/secret', 'settings' => ['pin' => '2468']])->assertOk();
    $this->postJson('/bio/ppin/links', ['type' => 'link', 'title' => 'Bad', 'url' => 'https://example.com/x', 'settings' => ['pin' => '12']])->assertStatus(422);
    $link = BioLink::where('title', 'Secret')->firstOrFail();
    auth()->logout();
    $this->get(route('bio.public.click', $link->public_id))->assertOk()->assertSee('PIN required');
    $this->post(route('bio.public.click.pin', $link->public_id), ['pin' => '0000'])->assertSee('Wrong PIN');
    $this->post(route('bio.public.click.pin', $link->public_id), ['pin' => '2468'])->assertOk()->assertSee('http-equiv="refresh"', false)->assertSee(route('bio.public.click', $link->public_id), false);
    $this->get(route('bio.public.click', $link->public_id))->assertRedirect('https://example.com/secret');
});
