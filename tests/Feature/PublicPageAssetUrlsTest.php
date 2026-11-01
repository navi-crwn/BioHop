<?php

use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;

test('icons, brand images and pictures on a public page point at the asset host, not at the host the page is served from', function () {
    config(['app.asset_url' => 'https://bio.hel.test']);
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'assets', 'title' => 'Assets', 'is_published' => true]);
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Own icon', 'url' => 'https://example.com', 'custom_icon' => '/storage/bio-icons/own.png', 'position' => 1]);
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Brand', 'url' => 'https://github.com/x', 'brand' => 'github', 'position' => 2]);

    $html = $this->get('/b/assets')->assertOk()->getContent();

    expect($html)->toContain('src="https://bio.hel.test/storage/bio-icons/own.png"')
        ->and($html)->toContain('https://bio.hel.test/images/brands/github.svg')
        ->and($html)->not->toContain('src="/storage/')
        ->and($html)->not->toContain('src="/images/brands/');
});

test('bio_media_url keeps full addresses and turns stored paths into asset-host addresses', function () {
    config(['app.asset_url' => 'https://bio.hel.test']);
    expect(bio_media_url(null))->toBeNull()
        ->and(bio_media_url(''))->toBeNull()
        ->and(bio_media_url('https://cdn.example.com/a.png'))->toBe('https://cdn.example.com/a.png')
        ->and(bio_media_url('/storage/bio-icons/a.png'))->toBe('https://bio.hel.test/storage/bio-icons/a.png')
        ->and(bio_media_url('/images/brands/github.svg'))->toBe('https://bio.hel.test/images/brands/github.svg')
        ->and(bio_media_url('bio-images/a.png'))->toEndWith('/storage/bio-images/a.png');
});
