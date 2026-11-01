<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;

test('the Design tab is six sections with a jump bar and a visitor preview link', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'design-tab', 'title' => 'Design', 'is_published' => true]);
    $html = $this->actingAs($user)->get('/bio/design-tab/edit')->assertOk()->getContent();
    foreach (['header', 'theme', 'wallpaper', 'text', 'buttons', 'footer'] as $section) {
        expect($html)->toContain('id="design-'.$section.'"');
    }
    expect($html)->toContain('Preview as visitor')->toContain('Button fill');
});

test('switching off "Powered by" in the Footer section removes it from the public page', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'design-footer', 'title' => 'Footer', 'is_published' => true]);
    $this->get('/b/design-footer')->assertOk()->assertSee('Powered by');
    $this->actingAs($user)->putJson('/bio/design-footer', ['hide_branding' => true])->assertOk();
    expect($page->fresh()->hide_branding)->toBeTrue();
    $this->get('/b/design-footer')->assertOk()->assertDontSee('Powered by');
});
