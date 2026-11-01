<?php

use App\Models\User;
use App\Support\ReservedSlugs;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;

function shopPage(string $slug): array
{
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => $slug, 'title' => 'Shop', 'is_published' => true]);

    return [$user, $page];
}

test('a product block is created through the editor with a price and currency', function () {
    [$user, $page] = shopPage('shop-create');
    $this->actingAs($user);
    $this->postJson("/bio/{$page->slug}/links", ['type' => 'product', 'title' => 'Linen shirt', 'url' => 'https://shop.example/linen', 'description' => 'Three colours', 'settings' => ['price' => '120000', 'currency' => 'IDR']])
        ->assertOk();
    $link = BioLink::where('bio_page_id', $page->id)->firstOrFail();
    expect($link->type)->toBe('product')->and($link->settings['price'])->toBe('120000')->and($link->priceLabel())->toContain('120')->toContain('IDR');
    $this->postJson("/bio/{$page->slug}/links", ['type' => 'product', 'title' => 'Bad', 'settings' => ['price' => 'free', 'currency' => 'RUPIAH']])->assertStatus(422);
});

test('the public page gets a Links and Shop tab bar once it sells something, and not before', function () {
    [, $page] = shopPage('shop-public');
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Portfolio', 'url' => 'https://example.com', 'order' => 0, 'is_active' => true]);
    $this->get('/b/shop-public')->assertOk()->assertDontSee('role="tablist"', false)->assertDontSee('class="bio-product', false);

    $sold = BioLink::create(['bio_page_id' => $page->id, 'type' => 'product', 'title' => 'Tote bag', 'url' => 'https://shop.example/tote', 'order' => 1, 'is_active' => true, 'settings' => ['price' => 20, 'currency' => 'USD', 'sold_out' => true]]);
    $open = BioLink::create(['bio_page_id' => $page->id, 'type' => 'product', 'title' => 'Linen shirt', 'url' => 'https://shop.example/linen', 'description' => 'Three colours', 'order' => 2, 'is_active' => true, 'settings' => ['price' => '120000', 'currency' => 'IDR']]);
    $html = $this->get('/b/shop-public')->assertOk()->getContent();
    expect($html)->toContain('role="tablist"')->toContain('Linen shirt')->toContain('Tote bag')->toContain('Sold out')->toContain('Three colours')
        ->toContain(route('bio.public.click', $open->public_id))
        ->not->toContain(route('bio.public.click', $sold->public_id));
    // the link stays in the Links panel, the products in the Shop panel
    expect(strpos($html, 'id="panel-links"'))->toBeGreaterThan(strpos($html, 'id="panel-shop"'));
    $this->get('/b/shop-public?tab=shop')->assertOk()->assertSee('id="tab-shop" aria-controls="panel-shop" aria-selected="true"', false);
});

test('the shop has its own address and a page without products has no tab bar there', function () {
    [, $page] = shopPage('shop-addr');
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Portfolio', 'url' => 'https://example.com', 'order' => 0, 'is_active' => true]);
    $this->get('/b/shop/shop-addr')->assertRedirect('/b/shop-addr');
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'product', 'title' => 'Mug', 'url' => 'https://shop.example/mug', 'order' => 1, 'is_active' => true, 'settings' => ['price' => 8, 'currency' => 'USD']]);
    $this->get('/b/shop/shop-addr')->assertOk()->assertSee('id="tab-shop" aria-controls="panel-shop" aria-selected="true"', false)->assertSee('href="'.route('bio.public.shop', 'shop-addr').'"', false);
    $this->get('/b/shop-addr')->assertOk()->assertSee('id="tab-links" aria-controls="panel-links" aria-selected="true"', false);
});

test('collections group the blocks under them on the page and in the shop', function () {
    [, $page] = shopPage('shop-coll');
    $mk = fn (array $a) => BioLink::create($a + ['bio_page_id' => $page->id, 'is_active' => true]);
    $mk(['type' => 'link', 'title' => 'Loose link', 'url' => 'https://example.com/a', 'order' => 0]);
    $mk(['type' => 'collection', 'title' => 'Watch', 'order' => 1, 'settings' => ['layout' => 'grid']]);
    $mk(['type' => 'link', 'title' => 'Film one', 'url' => 'https://example.com/b', 'order' => 2]);
    $mk(['type' => 'link', 'title' => 'Film two', 'url' => 'https://example.com/c', 'order' => 3]);
    $mk(['type' => 'collection', 'title' => 'Prints', 'order' => 4]);
    $mk(['type' => 'product', 'title' => 'Morning', 'url' => 'https://shop.example/1', 'order' => 5, 'settings' => ['price' => 10, 'currency' => 'USD']]);
    $mk(['type' => 'collection', 'title' => 'Empty one', 'order' => 6]);
    $html = $this->get('/b/shop-coll')->assertOk()->getContent();
    expect($html)->toContain('class="bio-collection layout-grid"')->toContain('Film one')->toContain('Film two')
        ->not->toContain('Empty one');
    // the link collection wraps exactly its two films: it opens after the loose link and closes before the shop section is irrelevant
    expect(strpos($html, 'Loose link'))->toBeLessThan(strpos($html, 'class="bio-collection layout-grid"'));
    expect(substr_count($html, '<section class="bio-collection'))->toBe(1);
    // the shop groups its product under Prints
    expect(strpos($html, 'Prints'))->toBeLessThan(strpos($html, 'Morning'));
});

test('an archived block leaves the page and the editor list, keeps its clicks and can be restored', function () {
    [$user, $page] = shopPage('shop-arch');
    $this->actingAs($user);
    $link = BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Old promo', 'url' => 'https://example.com/old', 'order' => 0, 'is_active' => true, 'click_count' => 42]);
    $this->postJson("/bio/shop-arch/links/{$link->id}/archive")->assertOk();
    expect($link->fresh()->archived_at)->not->toBeNull()->and($link->fresh()->click_count)->toBe(42);
    $this->get('/b/shop-arch')->assertOk()->assertDontSee('Old promo');
    $html = $this->get('/bio/shop-arch/edit')->assertOk()->getContent();
    preg_match('/<script type="application\/json" id="bio-editor-config">(.*?)<\/script>/s', $html, $m);
    $config = json_decode($m[1], true, flags: JSON_THROW_ON_ERROR);
    expect($config['blocks'])->toHaveCount(0)->and($config['archived'])->toHaveCount(1);
    $this->postJson("/bio/shop-arch/links/{$link->id}/restore")->assertOk();
    expect($link->fresh()->archived_at)->toBeNull();
    $this->get('/b/shop-arch')->assertOk()->assertSee('Old promo');
    // "shop" and "click" can no longer be page slugs
    expect(ReservedSlugs::isReserved('shop'))->toBeTrue()->and(ReservedSlugs::isReserved('click'))->toBeTrue();
});
