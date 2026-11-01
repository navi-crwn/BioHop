<?php

use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;

test('video, music and map blocks open as placeholders and load nothing from other sites until tapped', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'facade', 'title' => 'Facade', 'is_published' => true]);
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'youtube', 'title' => 'Our film', 'embed_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'order' => 0, 'is_active' => true]);
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'spotify', 'title' => 'Playlist', 'embed_url' => 'https://open.spotify.com/playlist/37i9dQZF1DXcBWIGoYBM5M', 'order' => 1, 'is_active' => true]);
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'map', 'title' => 'Studio', 'map_address' => 'Jl. Braga 1, Bandung', 'map_zoom' => 15, 'order' => 2, 'is_active' => true]);
    $html = $this->get('/b/facade')->assertOk()->getContent();
    expect($html)->not->toContain('<iframe')
        ->toContain('class="bio-facade bio-facade-video"')->toContain('data-embed-src="https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0"')
        ->toContain('href="https://www.youtube.com/watch?v=dQw4w9WgXcQ"')
        ->toContain('data-embed-src="https://open.spotify.com/embed/playlist/37i9dQZF1DXcBWIGoYBM5M')->toContain('data-embed-height="380"')
        ->toContain('class="bio-facade bio-facade-map"')->toContain('Loads from YouTube when you tap')->toContain('Loads from Google Maps when you tap');
});
