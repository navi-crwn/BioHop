<?php

use App\Models\User;
use App\Support\HopSignature;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Support\ImageMeta;
use Hop\Bio\Support\PixelHopMedia;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

// "Choose from PixelHop": the editor lists the member's own PixelHop pictures through a server-to-server call and
// keeps the picked file's public address. The file stays in PixelHop.

const PIXELHOP_SECRET = 'test-secret-test-secret-test-secret-1234'; // HOP_SSO_SECRET in phpunit.xml

function pickerOn(): void
{
    config(['bio.pixelhop_picker' => true, 'bio.pixelhop_url' => 'https://pixel.test', 'bio.pixelhop_hosts' => ['pixel.test', 'p.test']]);
}

function pixelhopItem(string $id, string $host = 'p.test', array $extra = []): array
{
    return $extra + [
        'id' => $id, 'url' => "https://{$host}/i/{$id}.jpg", 'thumb_url' => "https://{$host}/t/{$id}.jpg", 'page_url' => "https://{$host}/{$id}",
        'width' => 640, 'height' => 480, 'bytes' => 1234, 'mime' => 'image/jpeg', 'kind' => 'image', 'title' => 'Pic '.$id, 'alt' => '', 'created_at' => '2026-11-05T00:00:00Z', 'album' => 'Trip',
    ];
}

function pixelhopAnswers(array $items, array $page = []): void
{
    Http::fake(['https://pixel.test/api/v1/me/media*' => Http::response(['success' => true, 'data' => $page + ['items' => $items, 'next' => '', 'total' => count($items), 'hotlinks_blocked' => false]])]);
}

test('the picker route and buttons do not exist while the flag is off', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'pickeroff', 'title' => 'T']);
    $this->actingAs($user)->getJson('/bio/media/pixelhop')->assertNotFound();
    $html = $this->actingAs($user)->get('/bio/pickeroff/edit')->assertOk()->getContent();
    expect($html)->not->toContain('Choose from PixelHop')->not->toContain('pixelhop-picker');
    preg_match('/<script type="application\/json" id="bio-editor-config">(.*?)<\/script>/s', $html, $m);
    expect(json_decode($m[1], true)['pixelhop'])->toBeNull();
});

test('the editor offers the picker when the flag is on', function () {
    pickerOn();
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'pickeron', 'title' => 'T']);
    $html = $this->actingAs($user)->get('/bio/pickeron/edit')->assertOk()->getContent();
    expect($html)->toContain('Choose from PixelHop')->toContain('pixelhop-picker');
    preg_match('/<script type="application\/json" id="bio-editor-config">(.*?)<\/script>/s', $html, $m);
    $config = json_decode($m[1], true);
    expect($config['pixelhop']['url'])->toBe('http://localhost/bio/media/pixelhop')
        ->and($config['pixelhop']['hosts'])->toBe(['pixel.test', 'p.test'])
        ->and($html)->not->toContain('pixel.test/api'); // the browser is never given PixelHop's API address
});

test('the proxy lists the member\'s PixelHop pictures through a call signed as this app for that member', function () {
    pickerOn();
    pixelhopAnswers([pixelhopItem('beach-abc123'), pixelhopItem('city-def456', 'pixel.test', ['album' => ''])], ['next' => '2', 'total' => 5, 'hotlinks_blocked' => true]);
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/bio/media/pixelhop?type=image&limit=2')
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonCount(2, 'items')
        ->assertJsonPath('items.0.id', 'beach-abc123')
        ->assertJsonPath('items.0.url', 'https://p.test/i/beach-abc123.jpg')
        ->assertJsonPath('items.0.thumb_url', 'https://p.test/t/beach-abc123.jpg')
        ->assertJsonPath('items.0.width', 640)
        ->assertJsonPath('items.0.album', 'Trip')
        ->assertJsonPath('items.1.album', '')
        ->assertJsonPath('next', '2')
        ->assertJsonPath('total', 5)
        ->assertJsonPath('hotlinks_blocked', true);

    Http::assertSent(function (ClientRequest $request) use ($user) {
        $path = '/api/v1/me/media?type=image&limit=2';
        if ($request->url() !== 'https://pixel.test'.$path || $request->method() !== 'GET') {
            return false;
        }
        $expected = HopSignature::b64(hash_hmac('sha256', HopSignature::stringToSign(
            'biohop', $request->header('X-HOP-Timestamp')[0], $request->header('X-HOP-Nonce')[0], 'GET', $path, ''
        ), PIXELHOP_SECRET, true));

        return $request->header('X-HOP-App')[0] === 'biohop'
            && $request->header('X-HOP-Actor')[0] === (string) $user->id
            && strlen($request->header('X-HOP-Nonce')[0]) >= 16
            && hash_equals($expected, $request->header('X-HOP-Signature')[0])
            && ! $request->hasHeader('Authorization')
            && ! $request->hasHeader('Cookie');
    });
});

test('the cursor travels to PixelHop and files on a host that is not allowed are left out', function () {
    pickerOn();
    pixelhopAnswers([pixelhopItem('ok-aaaaaa'), pixelhopItem('bad-aaaaaa', 'evil.example'), ['id' => 'junk'], 'not even an array']);
    $this->actingAs(User::factory()->create())->getJson('/bio/media/pixelhop?cursor=40&type=pdf')
        ->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.id', 'ok-aaaaaa');
    Http::assertSent(fn (ClientRequest $r) => $r->url() === 'https://pixel.test/api/v1/me/media?type=pdf&cursor=40&limit=40');
});

test('a member who never signed in to PixelHop is told so, and an unreachable PixelHop means upload instead', function () {
    pickerOn();
    $user = User::factory()->create();
    Http::fake(['https://pixel.test/*' => Http::response(['success' => false, 'error' => 'no_account'], 404)]);
    $this->actingAs($user)->getJson('/bio/media/pixelhop')->assertNotFound()->assertJsonPath('code', 'no_account')
        ->assertJsonPath('message', 'Sign in to PixelHop once with your HOP ID; your pictures will then show up here.');

    Http::fake(['https://pixel.test/*' => fn () => throw new ConnectionException('timed out')]);
    $this->actingAs($user)->getJson('/bio/media/pixelhop')->assertStatus(503)->assertJsonPath('code', 'unavailable')
        ->assertJsonPath('message', 'PixelHop is not reachable right now. You can still upload a picture.');

    Http::fake(['https://pixel.test/*' => Http::response(['success' => false, 'error' => 'bad_signature'], 401)]);
    $this->actingAs($user)->getJson('/bio/media/pixelhop')->assertStatus(503)->assertJsonPath('code', 'unavailable');

    Http::fake(['https://pixel.test/*' => Http::response('<html>not json</html>', 200)]);
    $this->actingAs($user)->getJson('/bio/media/pixelhop')->assertStatus(503)->assertJsonPath('code', 'unavailable');
});

test('the proxy needs a signed-in member and refuses odd queries before calling PixelHop', function () {
    pickerOn();
    Http::fake();
    $this->getJson('/bio/media/pixelhop')->assertUnauthorized();
    $user = User::factory()->create();
    $this->actingAs($user)->getJson('/bio/media/pixelhop?type=video')->assertStatus(422)->assertJsonValidationErrors('type');
    $this->actingAs($user)->getJson('/bio/media/pixelhop?limit=101')->assertStatus(422)->assertJsonValidationErrors('limit');
    $this->actingAs($user)->getJson('/bio/media/pixelhop?cursor=../x')->assertStatus(422)->assertJsonValidationErrors('cursor');
    Http::assertNothingSent();
});

test('a PixelHop address is kept as the avatar only when it is on an allowed host and the picker is on', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'pickavatar', 'title' => 'T', 'avatar_url' => 'avatars/old.webp']);
    $this->actingAs($user);

    $this->putJson('/bio/pickavatar', ['avatar_url' => 'https://p.test/i/beach-abc123.jpg'])->assertOk();
    expect(BioPage::where('slug', 'pickavatar')->value('avatar_url'))->toBe('avatars/old.webp'); // picker off

    pickerOn();
    foreach (['https://evil.example/i/beach-abc123.jpg', 'http://p.test/i/beach-abc123.jpg', 'https://p.test/beach-abc123', 'https://p.test/i/beach-abc123.jpg?x=1', 'https://p.test@evil.example/i/x.jpg', 'avatars/someone-else.webp'] as $refused) {
        $this->putJson('/bio/pickavatar', ['avatar_url' => $refused])->assertOk();
        expect(BioPage::where('slug', 'pickavatar')->value('avatar_url'))->toBe('avatars/old.webp', $refused);
    }
    $this->putJson('/bio/pickavatar', ['avatar_url' => 'https://p.test/i/beach-abc123.jpg'])->assertOk();
    expect(BioPage::where('slug', 'pickavatar')->value('avatar_url'))->toBe('https://p.test/i/beach-abc123.jpg');
    // Clearing still works.
    $this->putJson('/bio/pickavatar', ['avatar_url' => null])->assertOk();
    expect(BioPage::where('slug', 'pickavatar')->value('avatar_url'))->toBeNull();
});

test('the cover, share picture, favicon and background take a PixelHop picture, also from a developer\'s plain-http PixelHop', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'pickcover', 'title' => 'T']);
    $this->actingAs($user);
    $dev = 'http://localhost:8090/i/cover-abc123.webp';
    $this->putJson('/bio/pickcover', ['cover_image' => $dev])->assertStatus(422)->assertJsonValidationErrors('cover_image'); // picker off: as before

    config(['bio.pixelhop_picker' => true, 'bio.pixelhop_url' => 'http://localhost:8090', 'bio.pixelhop_hosts' => ['p.test', 'http://localhost:8090']]);
    $this->putJson('/bio/pickcover', ['cover_image' => $dev, 'og_image' => 'https://p.test/i/og-abc123.jpg', 'favicon' => 'https://p.test/i/fav-abc123.png', 'background_image' => 'https://p.test/i/bg-abc123.jpg'])->assertOk();
    $page = BioPage::where('slug', 'pickcover')->first();
    expect($page->cover_image)->toBe($dev)->and($page->og_image)->toBe('https://p.test/i/og-abc123.jpg')
        ->and($page->favicon)->toBe('https://p.test/i/fav-abc123.png')->and($page->background_image)->toBe('https://p.test/i/bg-abc123.jpg');
    // Not a PixelHop file address: the old rules apply (https pictures elsewhere stay allowed, plain http is not).
    $this->putJson('/bio/pickcover', ['cover_image' => 'http://evil.example/i/x.jpg'])->assertStatus(422)->assertJsonValidationErrors('cover_image');
    $this->putJson('/bio/pickcover', ['cover_image' => 'https://img.example/x.jpg'])->assertOk();
});

test('a carousel takes PixelHop pictures next to uploads, including a developer\'s plain-http PixelHop', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'pickcarousel', 'title' => 'T']);
    $this->actingAs($user);
    $dev = 'http://localhost:8090/i/slide-abc123.webp';
    $block = ['type' => 'carousel', 'title' => 'Slides', 'settings' => ['images' => [['url' => $dev, 'caption' => 'one']]]];
    $this->postJson("/bio/{$page->slug}/links", $block)->assertStatus(422)->assertJsonValidationErrors('settings.images.0.url'); // picker off

    config(['bio.pixelhop_picker' => true, 'bio.pixelhop_url' => 'http://localhost:8090', 'bio.pixelhop_hosts' => ['p.test', 'http://localhost:8090']]);
    $block['settings']['images'][] = ['url' => 'https://p.test/i/slide-def456.jpg', 'caption' => 'two'];
    $block['settings']['images'][] = ['url' => '/storage/bio-images/local.webp', 'caption' => 'three'];
    $this->postJson("/bio/{$page->slug}/links", $block)->assertSuccessful();
    expect(BioLink::where('bio_page_id', $page->id)->value('settings')['images'])->toHaveCount(3);
    $block['settings']['images'] = [['url' => 'http://evil.example/i/x.jpg', 'caption' => '']];
    $this->postJson("/bio/{$page->slug}/links", $block)->assertStatus(422)->assertJsonValidationErrors('settings.images.0.url');
});

test('isFileUrl accepts a PixelHop file address and nothing else', function (string $url, bool $ok) {
    pickerOn();
    expect(PixelHopMedia::isFileUrl($url))->toBe($ok);
})->with([
    ['https://p.test/i/beach-abc123.jpg', true],
    ['https://PIXEL.test/i/a.webp', true],
    ['https://p.test/i/beach-abc123.jpg/../x', false],
    ['https://p.test/t/beach-abc123.jpg', false],
    ['https://p.test/i/beach abc.jpg', false],
    ['https://p.test:8443/i/a.jpg', false],
    ['https://p.test/i/a.jpg#k=secret', false],
    ['https://evil.example/i/a.jpg', false],
    ['javascript:alert(1)', false],
    ['', false],
]);

test('a plain-http origin is allowed only when the list names it that way, for a PixelHop on the developer\'s machine', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'pickcsp', 'title' => 'T']);
    $csp = (string) $this->actingAs($user)->get('/bio/pickcsp/edit')->assertOk()->headers->get('Content-Security-Policy');
    expect($csp)->not->toMatch('~img-src [^;]*http://~');

    config(['bio.pixelhop_hosts' => ['p.test', 'http://localhost:8090', 'bad entry', 'http://x.test/path']]);
    $csp = (string) $this->actingAs($user)->get('/bio/pickcsp/edit')->assertOk()->headers->get('Content-Security-Policy');
    expect($csp)->toMatch('~img-src [^;]*\bhttp://localhost:8090\b~')->not->toContain('x.test');
    expect(PixelHopMedia::hosts())->toBe(['p.test', 'http://localhost:8090'])
        ->and(PixelHopMedia::isFileUrl('http://localhost:8090/i/beach-abc123.jpg'))->toBeTrue()
        ->and(PixelHopMedia::isFileUrl('http://localhost/i/beach-abc123.jpg'))->toBeFalse()
        ->and(PixelHopMedia::isFileUrl('https://localhost:8090/i/beach-abc123.jpg'))->toBeFalse()
        ->and(PixelHopMedia::isFileUrl('http://p.test/i/beach-abc123.jpg'))->toBeFalse()
        ->and(PixelHopMedia::isFileUrl('https://p.test/i/beach-abc123.jpg'))->toBeTrue();
});

test('a public page shows a PixelHop picture from its own address, unchanged, and reads no file for it', function () {
    pickerOn();
    config(['app.asset_url' => 'https://bio.hel.test']);
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'pickpublic', 'title' => 'Public', 'is_published' => true,
        'avatar_url' => 'https://p.test/i/beach-abc123.jpg', 'favicon' => 'https://p.test/i/fav-abc123.png', 'cover_image' => 'https://p.test/i/cover-abc123.jpg', 'header_layout' => 'banner']);
    $html = $this->get('/b/pickpublic')->assertOk()->getContent();
    expect($html)->toContain('src="https://p.test/i/beach-abc123.jpg"')
        ->toContain('rel="icon" href="https://p.test/i/fav-abc123.png"')
        ->toContain('url(https://p.test/i/cover-abc123.jpg)')
        ->not->toContain('bio.hel.test/storage/https');
    expect(bio_media_url('https://p.test/i/beach-abc123.jpg'))->toBe('https://p.test/i/beach-abc123.jpg')
        ->and(ImageMeta::attrs('https://p.test/i/beach-abc123.jpg'))->toBe('');
});

test('deleting a page never touches a PixelHop file address', function () {
    pickerOn();
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'pickdelete', 'title' => 'T', 'avatar_url' => 'https://p.test/i/beach-abc123.jpg']);
    Storage::fake('public');
    Http::fake();
    $this->actingAs($user)->delete('/bio/pickdelete');
    Http::assertNothingSent();
    expect(BioPage::where('slug', 'pickdelete')->exists())->toBeFalse();
});

test('every place that takes a picture offers the picker, and none of them while the switch is off', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'pickplaces', 'title' => 'T']);
    $places = ['avatar', 'cover', 'favicon', 'image-block', 'image-new', 'carousel', 'background', 'og', 'product', 'icon'];

    $off = $this->actingAs($user)->get('/bio/pickplaces/edit')->assertOk()->getContent();
    foreach ($places as $place) {
        expect($off)->not->toContain("openPixelHop('{$place}')");
    }

    pickerOn();
    $on = $this->get('/bio/pickplaces/edit')->assertOk()->getContent();
    foreach ($places as $place) {
        expect($on)->toContain("openPixelHop('{$place}')");
    }
    // the dialog sits above the icon dialog (z-index 10001), which also offers it
    expect($on)->toContain('z-index: 10002');
    // the picture of the quick-add uploader takes a dropped file, and the words promise nothing else
    expect($on)->toContain('dropImageBlock($event)')->toContain('Drag and drop or click to upload');
    // the editor script knows every place it opens the dialog for
    $js = file_get_contents(base_path('resources/js/editor.js'));
    foreach (['avatar', 'cover', 'favicon', 'image-block', 'image-new', 'carousel', 'background', 'og', 'product', 'icon'] as $place) {
        expect($js)->toContain("case '{$place}'");
    }
});

test('a PixelHop picture is kept as the product picture and the block icon, and a stranger host stays what it was', function () {
    pickerOn();
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'pickprod', 'title' => 'T', 'is_published' => true]);
    $this->actingAs($user);
    $url = 'https://p.test/i/shirt-abc123.jpg';
    $icon = 'https://p.test/i/ico-abc123.png';

    $product = $this->postJson('/bio/pickprod/links', ['type' => 'product', 'title' => 'Shirt', 'url' => 'https://shop.example/s', 'thumbnail_url' => $url, 'content' => $url, 'settings' => ['price' => 10, 'currency' => 'IDR']])->assertOk();
    $link = $this->postJson('/bio/pickprod/links', ['type' => 'link', 'title' => 'Site', 'url' => 'https://example.com/', 'custom_icon' => $icon])->assertOk();
    $product = BioLink::where('bio_page_id', $page->id)->where('type', 'product')->value('id');
    $link = BioLink::where('bio_page_id', $page->id)->where('type', 'link')->value('id');
    expect(BioLink::find($product)->thumbnail_url)->toBe($url)->and(BioLink::find($link)->custom_icon)->toBe($icon);

    // saving again from the editor keeps them
    $this->putJson("/bio/pickprod/links/{$link}", ['custom_icon' => $icon, 'title' => 'Site'])->assertOk();
    expect(BioLink::find($link)->custom_icon)->toBe($icon);

    $html = $this->get('/b/pickprod')->assertOk()->getContent();
    expect($html)->toContain($icon);
    $this->get('/b/shop/pickprod')->assertOk()->assertSee($url, false);

    // the page's background and share picture: the address is kept on the page, as for the cover
    $this->putJson('/bio/pickprod', ['background_type' => 'image', 'background_image' => 'https://p.test/i/bg-abc123.jpg', 'background_value' => 'https://p.test/i/bg-abc123.jpg', 'og_image' => 'https://p.test/i/og-abc123.jpg'])->assertOk();
    $saved = BioPage::where('slug', 'pickprod')->first();
    expect($saved->background_value)->toBe('https://p.test/i/bg-abc123.jpg')->and($saved->og_image)->toBe('https://p.test/i/og-abc123.jpg');
    // a file address on a host that is not allowed is refused for the picture inputs that check hosts
    $this->putJson('/bio/pickprod', ['cover_image' => 'http://evil.example/i/x.jpg'])->assertStatus(422);
});
