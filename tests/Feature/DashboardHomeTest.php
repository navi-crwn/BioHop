<?php

use App\Models\User;
use Hop\Bio\Models\BioClick;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;

test('the bio home greets, counts this week against last week and shows the setup checklist', function () {
    $user = User::factory()->create(['name' => 'Helena Putri']);
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'home-check', 'title' => 'Helena', 'is_published' => false]);
    $link = BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Site', 'url' => 'https://example.com', 'order' => 0, 'is_active' => true]);
    foreach ([0, 1, 1, 3] as $d) {
        BioClick::create(['bio_link_id' => $link->id, 'clicked_at' => now()->subDays($d)]);
    }
    foreach ([8, 9] as $d) {
        BioClick::create(['bio_link_id' => $link->id, 'clicked_at' => now()->subDays($d)]);
    }
    $html = $this->actingAs($user)->get('/bio')->assertOk()->getContent();
    expect($html)->toContain(', Helena')->toContain('Clicks this week')->toContain('+100% against last week')
        ->toContain('1 of 6 complete')->toContain('Add a profile picture')->toContain('Publish the page')
        ->toContain('/bio/home-check/edit?tab=profile')->toContain('<polyline');

    $page->update(['is_published' => true, 'bio' => 'Hi', 'theme' => 'ocean', 'social_links' => [['platform' => 'x', 'value' => 'helena']]]);
    expect($this->get('/bio')->getContent())->toContain('5 of 6 complete');
});

test('the home without pages shows the empty state and no stats', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/bio')->assertOk()->assertSee('No bio pages yet')->assertDontSee('Clicks this week');
});
