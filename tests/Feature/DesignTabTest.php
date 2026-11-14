<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;

test('the Design tab is five sub-tabs, one section at a time, with a visitor preview link', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'design-tab', 'title' => 'Design', 'is_published' => true]);
    $html = $this->actingAs($user)->get('/bio/design-tab/edit')->assertOk()->getContent();
    foreach (['header', 'theme', 'wallpaper', 'text', 'buttons'] as $section) {
        expect($html)->toContain('id="design-'.$section.'"');
    }
    expect($html)->not->toContain('id="design-footer"');
    expect($html)->toContain('Preview as visitor')->toContain('Button fill')->toContain('role="tablist"')->toContain('role="tabpanel"')->not->toContain("toggle('header')");
    expect(substr_count($html, 'x-show="current ==='))->toBe(5);
});

test('the "Powered by" line is always shown on the public page, even for a page that had switched it off', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'design-footer', 'title' => 'Footer', 'is_published' => true]);
    $this->get('/b/design-footer')->assertOk()->assertSee('Powered by BioHop');
    // an old page whose owner had hidden it, and a request that still sends the old switch
    $this->actingAs($user)->putJson('/bio/design-footer', ['hide_branding' => true])->assertOk();
    expect($page->fresh()->hide_branding)->toBeTrue();
    $this->get('/b/design-footer')->assertOk()->assertSee('Powered by BioHop');
    // the editor no longer offers the switch, and its preview says what the page says
    $editor = $this->actingAs($user)->get('/bio/design-footer/edit')->assertOk()->getContent();
    expect($editor)->not->toContain('Show "Powered by" at the bottom');
    $this->get('/bio/design-footer/preview')->assertOk()->assertSee('Powered by BioHop')->assertDontSee('Powered by Hel.ink');
});
