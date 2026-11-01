<?php

use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Illuminate\Support\Facades\Storage;

/** A public page that uses every kind of stored file, with the asset host set apart from the page host. */
function mediaPage(array $attributes = []): BioPage
{
    config(['app.asset_url' => 'https://bio.hel.test', 'hop.public_url' => 'https://hel.test']);
    $user = User::factory()->create();
    $page = BioPage::create($attributes + ['user_id' => $user->id, 'slug' => 'media', 'title' => 'Media', 'is_published' => true]);
    $order = 0;
    foreach ([
        ['type' => 'image', 'title' => 'Pic', 'content' => '/storage/bio-images/pic.webp'],
        ['type' => 'audio', 'title' => 'Clip', 'content' => '/storage/bio-audio/clip.mp3'],
        ['type' => 'audio', 'title' => 'Clip two', 'content' => 'storage/bio-audio/two.mp3'],
        ['type' => 'pdf', 'title' => 'Doc', 'content' => '/storage/bio-files/doc.pdf'],
        ['type' => 'carousel', 'title' => 'Slides', 'settings' => ['images' => [['url' => '/storage/bio-images/s1.webp', 'caption' => ''], ['url' => 'storage/bio-images/s2.webp', 'caption' => ''], ['url' => 'https://img.example/s3.jpg', 'caption' => '']]]],
        ['type' => 'link', 'title' => 'Thumb', 'url' => 'https://example.com', 'thumbnail_url' => '/storage/bio-thumbnails/t.webp'],
        ['type' => 'product', 'title' => 'Shirt', 'url' => 'https://shop.example/shirt', 'thumbnail_url' => '/storage/bio-thumbnails/p.webp', 'settings' => ['price' => 10, 'currency' => 'USD']],
    ] as $block) {
        BioLink::create($block + ['bio_page_id' => $page->id, 'order' => ++$order, 'is_active' => true]);
    }

    return $page;
}

test('every picture, icon and file on a public page is addressed in full on the asset host', function () {
    mediaPage([
        'avatar_url' => 'avatars/me.webp', 'cover_image' => '/storage/bio-images/cover.webp', 'favicon' => '/storage/bio-images/fav.png',
        'og_image' => '/storage/bio-images/og.webp', 'header_layout' => 'banner',
        'background_type' => 'image', 'background_image' => 'bio-images/bg.webp',
        'qr_settings' => ['logo_url' => '/storage/qr-logos/logo.png'],
    ]);

    $html = $this->get('/b/media')->assertOk()->getContent();

    foreach ([
        'src="https://bio.hel.test/storage/avatars/me.webp"',
        'rel="apple-touch-icon" href="https://bio.hel.test/storage/avatars/me.webp"',
        'rel="icon" href="https://bio.hel.test/storage/bio-images/fav.png"',
        'property="og:image" content="https://bio.hel.test/storage/bio-images/og.webp"',
        'url(https://bio.hel.test/storage/bio-images/cover.webp)',
        "url('https://bio.hel.test/storage/bio-images/bg.webp')",
        'src="https://bio.hel.test/storage/bio-images/pic.webp"',
        '<audio controls preload="none" class="w-full" src="https://bio.hel.test/storage/bio-audio/clip.mp3">',
        'src="https://bio.hel.test/storage/bio-audio/two.mp3"',
        'href="https://bio.hel.test/storage/bio-files/doc.pdf"',
        'src="https://bio.hel.test/storage/bio-images/s1.webp"',
        'src="https://bio.hel.test/storage/bio-images/s2.webp"',
        'src="https://img.example/s3.jpg"',
        'src="https://bio.hel.test/storage/bio-thumbnails/t.webp"',
        'src="https://bio.hel.test/storage/bio-thumbnails/p.webp"',
        "const qrLogoUrl = 'https:\/\/bio.hel.test\/storage\/qr-logos\/logo.png'",
    ] as $expected) {
        expect($html)->toContain($expected);
    }
    // nothing is left as a bare path that the page host would be asked for
    expect($html)->not->toMatch('#(src|href|content)="/?(storage|images|fonts|build)/#')
        ->and($html)->not->toMatch('#url\(\'?/?storage/#');
});

test('the og image falls back to the profile picture and stays absolute', function () {
    mediaPage(['avatar_url' => 'avatars/me.webp']);

    $this->get('/b/media')->assertOk()->assertSee('property="og:image" content="https://bio.hel.test/storage/avatars/me.webp"', false);
});

test('the story card draws the profile picture from the asset host', function () {
    mediaPage(['avatar_url' => 'avatars/me.webp']);

    $this->get('/b/media')->assertOk()->assertSee("avatar = 'https:\/\/bio.hel.test\/storage\/avatars\/me.webp'", false);
});

test('the share dialog loads the bundled QR library, not a CDN', function () {
    mediaPage();

    $this->get('/b/media')->assertOk()->assertDontSee('unpkg.com', false)->assertSee('window.loadQr()', false);
});

test('the public page font is addressed on the asset host', function () {
    mediaPage(['font_family' => 'poppins']);

    $html = $this->get('/b/media')->assertOk()->getContent();

    expect($html)->toMatch('#<link rel="preload" as="font" type="font/woff2" href="https://bio\.hel\.test/fonts/bio/poppins-[0-9a-f]+\.woff2" crossorigin>#')
        ->and($html)->toMatch('#src: url\(https://bio\.hel\.test/fonts/bio/poppins-[0-9a-f]+\.woff2\)#')
        ->and($html)->not->toContain('url(/fonts/')
        ->and($html)->not->toContain('href="/fonts/');
});

test('the page manifest is on the page host and its icon on the asset host', function () {
    mediaPage(['avatar_url' => 'avatars/me.png']);
    Storage::fake('public');
    Storage::disk('public')->put('avatars/me.png', file_get_contents(public_path('images/brand/rabbit-mark.png')));

    $this->get('/b/media')->assertOk()->assertSee('rel="manifest" href="https://hel.test/b/media/manifest.webmanifest"', false);
    $this->get('/b/media/manifest.webmanifest')->assertOk()->assertJsonPath('icons.0.src', 'https://bio.hel.test/storage/avatars/me.png');
});

test('the content security policy lets the public host load fonts, media and scripts from the asset host and frame the official page', function () {
    mediaPage();

    $csp = $this->get('/b/media')->assertOk()->headers->get('Content-Security-Policy');

    expect($csp)->toMatch("#font-src 'self' https://bio\\.hel\\.test #")
        ->and($csp)->toMatch("#media-src 'self' https://bio\\.hel\\.test;#")
        ->and($csp)->toMatch("#script-src 'self' https://bio\\.hel\\.test #")
        ->and($csp)->toMatch("#connect-src 'self' https://bio\\.hel\\.test #")
        ->and($csp)->toMatch("#frame-src 'self' https://hel\\.test #");
});

test('without ASSET_URL the asset host is APP_URL', function () {
    config(['app.asset_url' => null, 'app.url' => 'https://bio.hel.test/']);

    expect(bio_asset_origin())->toBe('https://bio.hel.test')
        ->and(bio_media_url('/storage/bio-icons/a.png'))->toBe('https://bio.hel.test/storage/bio-icons/a.png');

    config(['app.asset_url' => 'https://assets.hel.test/']);
    expect(bio_asset_origin())->toBe('https://assets.hel.test');
});

test('bio_media_url handles every shape the database holds', function () {
    config(['app.asset_url' => 'https://bio.hel.test']);

    expect(bio_media_url('/storage/bio-audio/a.mp3'))->toBe('https://bio.hel.test/storage/bio-audio/a.mp3')
        ->and(bio_media_url('storage/bio-audio/a.mp3'))->toBe('https://bio.hel.test/storage/bio-audio/a.mp3')
        ->and(bio_media_url('avatars/a.webp'))->toBe('https://bio.hel.test/storage/avatars/a.webp')
        ->and(bio_media_url('  /storage/x.png '))->toBe('https://bio.hel.test/storage/x.png')
        ->and(bio_media_url('//cdn.example/a.png'))->toBe('//cdn.example/a.png')
        ->and(bio_media_url('data:image/png;base64,AAAA'))->toBe('data:image/png;base64,AAAA')
        ->and(bio_media_url('http://a.example/x.png'))->toBe('http://a.example/x.png');
});

test('the account picture from the hub is used as it is and a bare path goes on the hub host', function () {
    config(['hop.hub_url' => 'https://hub.hel.test']);
    $user = User::factory()->make();

    $user->avatar = 'https://hub.hel.test/storage/avatars/u.webp';
    $user->avatar_path = 'avatars/u.webp';
    expect($user->avatarUrl())->toBe('https://hub.hel.test/storage/avatars/u.webp');

    $user->avatar = 'https://lh3.googleusercontent.com/a/photo';
    $user->avatar_path = null;
    expect($user->avatarUrl())->toBe('https://lh3.googleusercontent.com/a/photo');

    $user->avatar = null;
    foreach (['avatars/u.webp', 'storage/avatars/u.webp', '/storage/avatars/u.webp'] as $stored) {
        $user->avatar_path = $stored;
        expect($user->avatarUrl())->toBe('https://hub.hel.test/storage/avatars/u.webp');
    }

    $user->avatar_path = null;
    expect($user->avatarUrl())->toBeNull();
});
