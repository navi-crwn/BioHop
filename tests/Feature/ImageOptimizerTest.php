<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Support\ImageMeta;
use Hop\Bio\Support\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

function bigJpeg(int $w = 3000, int $h = 2000): UploadedFile
{
    $img = imagecreatetruecolor($w, $h);
    imagefilledrectangle($img, 0, 0, $w, $h, imagecolorallocate($img, 200, 60, 90));
    for ($i = 0; $i < 60; $i++) {
        imagefilledellipse($img, random_int(0, $w), random_int(0, $h), random_int(50, 400), random_int(50, 400), imagecolorallocate($img, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
    }
    $path = tempnam(sys_get_temp_dir(), 'bj').'.jpg';
    imagejpeg($img, $path, 95);

    return new UploadedFile($path, 'photo.jpg', 'image/jpeg', null, true);
}

test('a big photo is scaled to the size the page can show and stored as WebP', function () {
    Storage::fake('public');
    $file = bigJpeg();
    $original = $file->getSize();
    $path = ImageOptimizer::store($file, 'avatars');
    expect($path)->toStartWith('avatars/')->toEndWith('.webp');
    $bytes = Storage::disk('public')->get($path);
    [$w, $h] = getimagesizefromstring($bytes);
    expect(max($w, $h))->toBe(640)->and($w / $h)->toBeGreaterThan(1.4)->and(strlen($bytes))->toBeLessThan($original / 3);
    // a picture already smaller than the limit is not enlarged
    $small = ImageOptimizer::store(bigJpeg(300, 200), 'bio-thumbnails');
    [$sw] = getimagesizefromstring(Storage::disk('public')->get($small));
    expect($sw)->toBe(240 < 300 ? 240 : 300);
    $tiny = ImageOptimizer::store(bigJpeg(120, 80), 'bio-thumbnails');
    expect(getimagesizefromstring(Storage::disk('public')->get($tiny))[0])->toBe(120);
});

test('the avatar upload endpoint shrinks the picture and the page learns its size and colour', function () {
    Storage::fake('public');
    Cache::flush();
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'imgp', 'title' => 'Img', 'is_published' => true]);
    $res = $this->actingAs($user)->post('/bio/imgp/upload-avatar', ['avatar' => bigJpeg(2400, 2400)], ['Accept' => 'application/json'])->assertOk()->json();
    expect($res['avatar_url'])->toEndWith('.webp');
    $meta = ImageMeta::for($res['avatar_url']);
    expect($meta['w'])->toBe(640)->and($meta['h'])->toBe(640)->and($meta['color'])->toMatch('/^#[0-9a-f]{6}$/');
    $page->update(['header_layout' => 'hero']);
    $html = $this->get('/b/imgp')->assertOk()->getContent();
    expect($html)->toContain('width="640" height="640"')->toContain('background-color:#');
    expect(ImageMeta::for('https://elsewhere.example/a.jpg'))->toBeNull()->and(ImageMeta::for('avatars/logo.svg'))->toBeNull();
});

test('a heavy animated GIF is refused, a light one is kept, and a favicon stays a small PNG', function () {
    Storage::fake('public');
    $gif = function (int $frames, int $size) {
        $head = "GIF89a\x02\x00\x02\x00\x80\x00\x00\x00\x00\x00\xff\xff\xff";
        $frame = "\x21\xF9\x04\x00\x0A\x00\x00\x00\x2C\x00\x00\x00\x00\x02\x00\x02\x00\x00\x02\x02\x44\x01\x00";
        $bytes = $head.str_repeat($frame, $frames).str_repeat("\x21\xFE\x01x\x00", (int) ($size / 5)).';';
        $path = tempnam(sys_get_temp_dir(), 'g').'.gif';
        file_put_contents($path, $bytes);

        return new UploadedFile($path, 'a.gif', 'image/gif', null, true);
    };
    expect(ImageOptimizer::isAnimatedGif(file_get_contents($gif(3, 10)->getRealPath())))->toBeTrue();
    expect(ImageOptimizer::store($gif(3, 100), 'bio-thumbnails'))->toEndWith('.gif');
    expect(fn () => ImageOptimizer::store($gif(3, 1_200_000), 'bio-thumbnails'))->toThrow(ValidationException::class);
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'favp', 'title' => 'Fav', 'is_published' => true]);
    $res = $this->actingAs($user)->post('/bio/favp/upload-image', ['image' => bigJpeg(900, 900), 'format' => 'png'], ['Accept' => 'application/json'])->assertOk()->json();
    expect($res['path'])->toEndWith('.png');
    expect(max(...array_slice(getimagesizefromstring(Storage::disk('public')->get($res['path'])), 0, 2)))->toBe(128);
});

test('the backfill command shrinks old pictures, updates what points to them and leaves favicons alone', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $png = function () {
        $img = imagecreatetruecolor(1800, 1800);
        for ($i = 0; $i < 400; $i++) {
            imagefilledrectangle($img, random_int(0, 1800), random_int(0, 1800), random_int(0, 1800), random_int(0, 1800), imagecolorallocate($img, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
        }
        ob_start();
        imagepng($img);

        return ob_get_clean();
    };
    Storage::disk('public')->put('avatars/old.png', $png());
    Storage::disk('public')->put('bio-images/fav.png', $png());
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'backf', 'title' => 'B', 'is_published' => true, 'avatar_url' => 'avatars/old.png', 'favicon' => 'bio-images/fav.png']);
    $link = $page->links()->create(['type' => 'carousel', 'title' => 'c', 'order' => 0, 'is_active' => true, 'settings' => ['images' => [['url' => '/storage/avatars/old.png', 'caption' => '']]]]);

    $this->artisan('bio:optimize-images --min=1')->assertSuccessful();
    expect($page->fresh()->avatar_url)->toBe('avatars/old.png');
    $this->artisan('bio:optimize-images --apply --min=1')->assertSuccessful();
    $new = $page->fresh()->avatar_url;
    expect($new)->toEndWith('.webp')->and($page->fresh()->favicon)->toBe('bio-images/fav.png')
        ->and($link->fresh()->settings['images'][0]['url'])->toBe('/storage/'.$new);
    Storage::disk('public')->assertMissing('avatars/old.png');
    Storage::disk('public')->assertExists($new);
    Storage::disk('public')->assertExists('bio-images/fav.png');
});

/** A small file that says it is a huge PNG: the header is all a size check reads. */
function hugePng(int $w = 20000, int $h = 20000): UploadedFile
{
    $chunk = fn (string $type, string $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    $path = tempnam(sys_get_temp_dir(), 'hp').'.png';
    file_put_contents($path, "\x89PNG\r\n\x1a\n".$chunk('IHDR', pack('NN', $w, $h)."\x08\x02\x00\x00\x00").$chunk('IDAT', gzcompress(str_repeat("\0", 64))).$chunk('IEND', ''));

    return new UploadedFile($path, 'huge.png', 'image/png', null, true);
}

test('a picture with too many pixels gets a 422 on every upload, and nothing is stored', function (string $path, string $field) {
    Storage::fake('public');
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'hugepx', 'title' => 'T']);
    $this->actingAs($user)->postJson("/bio/hugepx/{$path}", [$field => hugePng()])
        ->assertStatus(422)->assertJsonValidationErrors($field);
    expect(Storage::disk('public')->allFiles())->toBe([]);
})->with([
    'avatar' => ['upload-avatar', 'avatar'],
    'image' => ['upload-image', 'image'],
    'thumbnail' => ['upload-thumbnail', 'thumbnail'],
    'icon' => ['upload-icon', 'icon'],
    'qr logo' => ['upload-qr-logo', 'logo'],
]);

test('a file over the size limit gets a 422 on every upload', function (string $path, string $field, int $kb) {
    Storage::fake('public');
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'heavy', 'title' => 'T']);
    $this->actingAs($user)->postJson("/bio/heavy/{$path}", [$field => UploadedFile::fake()->create('big.png', $kb, 'image/png')])
        ->assertStatus(422)->assertJsonValidationErrors($field);
})->with([
    'avatar' => ['upload-avatar', 'avatar', 8193],
    'image' => ['upload-image', 'image', 10241],
    'qr logo' => ['upload-qr-logo', 'logo', 4097],
]);

test('a QR logo of a normal size is still stored as it was sent', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'qrok', 'title' => 'T']);
    $response = $this->actingAs($user)->postJson('/bio/qrok/upload-qr-logo', ['logo' => UploadedFile::fake()->image('logo.png', 200, 200)])->assertOk();
    expect($response->json('logo_url'))->toStartWith('/storage/qr-logos/')
        ->and($page->fresh()->qr_settings['logo_url'])->toBe($response->json('logo_url'));
});

test('a QR logo sent inside the settings has the same pixel limit', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'qrdata', 'title' => 'T']);
    $data = 'data:image/png;base64,'.base64_encode(file_get_contents(hugePng()->getRealPath()));
    $this->actingAs($user)->putJson('/bio/qrdata', ['qr_settings' => ['image' => $data]])->assertStatus(422)->assertJsonValidationErrors('qr_settings.image');
    expect(Storage::disk('public')->allFiles())->toBe([]);
    $small = imagecreatetruecolor(32, 32);
    ob_start();
    imagepng($small);
    $png = 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
    $this->putJson('/bio/qrdata', ['qr_settings' => ['image' => $png]])->assertOk();
    expect(Storage::disk('public')->allFiles())->toHaveCount(1);
});
