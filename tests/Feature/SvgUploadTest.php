<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Support\SvgSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function svgFile(string $markup, string $name = 'icon.svg'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $markup);
}

test('a plain svg icon uploads and comes back as an svg file', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'svgicon', 'title' => 'Icon']);
    $this->actingAs($user);
    $response = $this->postJson('/bio/svgicon/upload-icon', ['icon' => svgFile('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M2 2h20v20H2z" fill="#f00"/></svg>')]);
    $response->assertOk()->assertJsonPath('success', true);
    $path = ltrim(str_replace('/storage/', '', $response->json('icon_url')), '/');
    expect($path)->toEndWith('.svg');
    Storage::disk('public')->assertExists($path);
    expect(Storage::disk('public')->get($path))->toContain('<path');
});

test('an svg without an xml prolog or namespace still works (the case finfo mislabels as text)', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'svgbare', 'title' => 'Icon']);
    $this->actingAs($user);
    $this->postJson('/bio/svgbare/upload-icon', ['icon' => svgFile("\n  <svg viewBox=\"0 0 8 8\"><circle cx=\"4\" cy=\"4\" r=\"3\"/></svg>")])->assertOk();
});

test('scripts, handlers, external references and foreign content are stripped from an svg', function () {
    $dirty = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" onload="alert(1)" viewBox="0 0 10 10">'
        .'<script>alert(1)</script><foreignObject><iframe src="https://evil.test"></iframe></foreignObject>'
        .'<a xlink:href="javascript:alert(1)"><rect width="5" height="5" onclick="x()" style="fill:url(https://evil.test/x)"/></a>'
        .'<image href="https://evil.test/tracker.png"/><use href="https://evil.test/a.svg#x"/>'
        .'<style>@import url(https://evil.test/a.css); rect{fill:red}</style>'
        .'<circle cx="5" cy="5" r="2" fill="url(#g)"/><defs><linearGradient id="g"><stop offset="0" stop-color="#000"/></linearGradient></defs></svg>';
    $clean = SvgSanitizer::clean($dirty);
    expect($clean)->not->toBeNull();
    foreach (['script', 'onload', 'onclick', 'foreignObject', 'iframe', 'evil.test', 'javascript:', '@import', '<image'] as $bad) {
        expect($clean)->not->toContain($bad);
    }
    // what is harmless stays
    expect($clean)->toContain('<circle')->toContain('url(#g)')->toContain('linearGradient');
});

test('entities, doctypes and files that are not svg are refused', function () {
    expect(SvgSanitizer::clean('<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]><svg xmlns="http://www.w3.org/2000/svg">&x;</svg>'))->toBeNull();
    expect(SvgSanitizer::clean('<html><body>hi</body></html>'))->toBeNull();
    expect(SvgSanitizer::clean('not xml at all'))->toBeNull();
    expect(SvgSanitizer::clean(str_repeat('a', SvgSanitizer::MAX_BYTES + 1)))->toBeNull();

    Storage::fake('public');
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'svgbad', 'title' => 'Icon']);
    $this->actingAs($user);
    $this->postJson('/bio/svgbad/upload-icon', ['icon' => svgFile('<html><script>alert(1)</script></html>', 'evil.svg')])
        ->assertStatus(422)->assertJsonValidationErrors('icon');
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

test('icons, logos and thumbnails accept svg; avatars and block images stay raster only', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'svgmix', 'title' => 'Mix']);
    $this->actingAs($user);
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 4 4"><rect width="4" height="4"/></svg>';
    $this->postJson('/bio/svgmix/upload-thumbnail', ['thumbnail' => svgFile($svg)])->assertOk();
    $this->postJson('/bio/svgmix/upload-qr-logo', ['logo' => svgFile($svg)])->assertOk();
    $this->postJson('/bio/svgmix/upload-avatar', ['avatar' => svgFile($svg)])->assertStatus(422);
    $this->postJson('/bio/svgmix/upload-image', ['image' => svgFile($svg)])->assertStatus(422);
    // a png still goes through the old path
    $this->postJson('/bio/svgmix/upload-icon', ['icon' => UploadedFile::fake()->image('i.png', 64, 64)])->assertOk();
});

// What a file input offers must be what its endpoint takes (A-5): SVG only where it is accepted (icons, QR logo), never on
// the profile picture, pictures, cover, background, share picture or favicon, and the editor says so in nine languages.
test('every picture input offers exactly the types its endpoint takes', function () {
    $raster = 'accept="image/png,image/jpeg,image/gif,image/webp,image/bmp"';
    $withSvg = 'accept="image/png,image/jpeg,image/gif,image/webp,image/bmp,image/svg+xml,.svg"';
    $views = base_path('packages/bio/resources/views/');
    $inputs = [];
    foreach (['editor/index', 'editor/profile-tab', 'editor/appearance-tab', 'editor/settings-tab', 'create-wizard', 'create'] as $view) {
        $html = file_get_contents($views.$view.'.blade.php');
        preg_match_all('/<input\b[^>]*type="file"[^>]*>/s', $html, $found);
        foreach ($found[0] as $tag) {
            if (preg_match('/@change="(\w+)/', $tag, $m) && preg_match('/(?<![:\w])accept="[^"]*"/', $tag, $a)) {
                $inputs[$view.':'.$m[1]][] = $a[0];
            }
        }
    }
    foreach (['editor/index:uploadCustomIcon', 'editor/profile-tab:uploadLogo'] as $key) {
        expect($inputs[$key])->each->toBe($withSvg);
    }
    foreach (['editor/index:uploadBlockImage', 'editor/index:uploadCarouselImage', 'editor/index:uploadImageBlock', 'editor/profile-tab:uploadAvatar',
        'editor/appearance-tab:uploadCoverImage', 'editor/appearance-tab:uploadBackgroundImage', 'editor/settings-tab:uploadPageImage', 'create-wizard:pickAvatar'] as $key) {
        expect($inputs[$key])->each->toBe($raster);
    }
    // the classic form sends its QR logo as a data URI, which the server keeps only for PNG, JPG and GIF
    expect($inputs['create:uploadLogo'])->toBe(['accept="image/png,image/jpeg,image/gif"']);
    expect(collect($inputs)->flatten()->filter(fn ($a) => $a === 'accept="image/*"'))->toBeEmpty();

    $message = 'SVG cannot be used here. Use a PNG, JPG, WebP or GIF picture.';
    foreach (['zh-CN', 'de', 'id', 'ja', 'pt-BR', 'es', 'fr', 'ru'] as $locale) {
        $lang = json_decode(file_get_contents(lang_path($locale.'.json')), true);
        expect($lang)->toHaveKey($message);
        expect($lang[$message])->not->toBe($message);
    }
});
