<?php

use App\Models\User;
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
