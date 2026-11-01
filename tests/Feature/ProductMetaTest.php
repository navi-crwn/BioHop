<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Support\ProductMeta;
use Hop\Safety\UrlGuard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Cache::flush();
    app()->bind(UrlGuard::class, fn () => new class extends UrlGuard
    {
        protected function resolveHost(string $host): array
        {
            return in_array($host, ['localhost', 'internal.example'], true) ? ['127.0.0.1'] : ['93.184.216.34'];
        }
    });
});

const SHOP_HTML = '<html><head><title>Fallback title</title>'
    .'<meta property="og:title" content="Linen shirt &amp; more"><meta property="og:description" content="Organic linen, three colours.">'
    .'<meta property="og:image" content="/img/shirt.jpg"><meta property="product:price:amount" content="120.000"><meta property="product:price:currency" content="idr">'
    .'</head><body></body></html>';

test('a shop page gives its title, description, picture, price and currency from its public tags', function () {
    $m = ProductMeta::parse(SHOP_HTML, 'https://shop.example/p/1');
    expect($m)->toBe(['title' => 'Linen shirt & more', 'description' => 'Organic linen, three colours.', 'image' => 'https://shop.example/img/shirt.jpg', 'price' => 120000.0, 'currency' => 'IDR']);
    $ld = '<script type="application/ld+json">{"@context":"https://schema.org","@type":"Product","name":"Tote","image":["https://s.example/t.jpg"],"offers":{"@type":"Offer","price":"20.50","priceCurrency":"USD"}}</script>';
    expect(ProductMeta::parse('<html><head>'.$ld.'</head></html>'))->toMatchArray(['title' => 'Tote', 'image' => 'https://s.example/t.jpg', 'price' => 20.5, 'currency' => 'USD']);
    expect(ProductMeta::parse('<html><head><meta property="og:image" content="http://insecure.example/a.jpg"></head></html>')['image'])->toBeNull();
});

test('the editor endpoint fills from a shop, keeps the picture in our own storage, and fails softly', function () {
    Storage::fake('public');
    $jpeg = (function () {
        $img = imagecreatetruecolor(900, 900);
        imagefilledrectangle($img, 0, 0, 900, 900, imagecolorallocate($img, 20, 120, 200));
        ob_start();
        imagejpeg($img);

        return ob_get_clean();
    })();
    Http::fake([
        'shop.example/p/1' => Http::response(SHOP_HTML, 200, ['Content-Type' => 'text/html; charset=utf-8']),
        'shop.example/img/shirt.jpg' => Http::response($jpeg, 200, ['Content-Type' => 'image/jpeg']),
        'slow.example/*' => Http::response('nope', 503),
    ]);
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'metap', 'title' => 'Meta', 'is_published' => true]);
    $res = $this->actingAs($user)->postJson('/bio/metap/links/fetch-meta', ['url' => 'https://shop.example/p/1'])->assertOk()->json();
    expect($res['title'])->toBe('Linen shirt & more')->and($res['price'])->toBe(120000)->and($res['currency'])->toBe('IDR')
        ->and($res['thumbnail_url'])->toStartWith('/storage/bio-thumbnails/')->toEndWith('.webp')->and($res['found'])->toContain('picture', 'price');
    Storage::disk('public')->assertExists(str_replace('/storage/', '', $res['thumbnail_url']));
    // a site that fails answers softly, and is not asked again for a while
    $this->postJson('/bio/metap/links/fetch-meta', ['url' => 'https://slow.example/x'])->assertStatus(422)->assertJsonPath('success', false);
    $this->postJson('/bio/metap/links/fetch-meta', ['url' => 'https://slow.example/y'])->assertStatus(422)->assertJsonFragment(['message' => 'This site did not answer a moment ago. Type the details yourself.']);
    // private addresses and non-web addresses are refused
    $this->postJson('/bio/metap/links/fetch-meta', ['url' => 'http://localhost/admin'])->assertStatus(422);
    $this->postJson('/bio/metap/links/fetch-meta', ['url' => 'javascript:alert(1)'])->assertStatus(422);
    $other = User::factory()->create();
    $this->actingAs($other)->postJson('/bio/metap/links/fetch-meta', ['url' => 'https://shop.example/p/1'])->assertForbidden();
});
