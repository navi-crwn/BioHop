<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

test('the existing-link picker asks the registered search route, which answers a list', function () {
    Http::fake(['https://link.test/api/me/links*' => Http::response(['data' => [
        ['id' => 1, 'title' => 'Demo', 'url' => 'https://example.com/page', 'short_url' => 'https://link.test/l/demo'],
    ]])]);

    $this->actingAs(User::factory()->create())
        ->getJson('/bio/links/search?query=demo')
        ->assertOk()
        ->assertJsonPath('0.short_url', 'https://link.test/l/demo');

    expect(file_get_contents(base_path('resources/js/editor.js')))
        ->toContain("fetch('/bio/links/search?query=' + encodeURIComponent(asked)")
        ->not->toContain("fetch('/api/links?query=");
});

test('the editor only calls /api routes that this app registers', function () {
    $registered = collect(app('router')->getRoutes()->getRoutes())->map(fn ($route) => '/'.ltrim($route->uri(), '/'));
    $sources = [base_path('resources/js/editor.js'), base_path('packages/bio/resources/views/editor/index.blade.php')];

    foreach ($sources as $file) {
        preg_match_all("#['\"`](/api/[A-Za-z0-9/_-]*)#", file_get_contents($file), $found);
        foreach ($found[1] as $path) {
            expect($registered->contains(fn ($uri) => str_starts_with($uri, rtrim($path, '/'))))->toBeTrue("{$file} calls {$path}, which is not a registered route");
        }
    }
});

// The editor addresses a page by its (saved) slug: the routes bind {bioPage} by slug, so an id gave a 404 (A-17).
test('every page request of the editor goes to a registered route, by slug', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn ($route) => $route->uri())
        ->filter(fn ($uri) => str_starts_with($uri, 'bio/{bioPage}'))
        ->map(fn ($uri) => preg_replace('/\{\w+\}/', '*', substr($uri, strlen('bio/{bioPage}'))));
    $files = [base_path('resources/js/editor.js'), ...glob(base_path('packages/bio/resources/views/editor/*.blade.php'))];
    $paths = [];
    foreach ($files as $file) {
        $source = file_get_contents($file);
        expect($source)->not->toMatch('#[\'"`]/bio/\$\{[^}]*\.id\}#');
        // the literal start of each call; `${x}` in a template stands for one path part; "+ …" means more follows
        preg_match_all('#bioEditorUrl\(\s*[\'"`]([^\'"`]*)[\'"`]\s*(\+)?#', $source, $found, PREG_SET_ORDER);
        foreach ($found as $call) {
            $paths[] = [preg_replace('#\$\{[^}]*\}#', '*', $call[1]), ($call[2] ?? '') === '+', basename($file)];
        }
    }
    expect(collect($paths)->pluck(0))->toContain('/upload-qr-logo');
    foreach ($paths as [$path, $joined, $file]) {
        $ok = $routes->contains(fn ($uri) => $joined ? str_starts_with($uri, rtrim($path, '/')) : fnmatch($uri, $path));
        expect($ok)->toBeTrue("{$file} asks bioEditorUrl('{$path}'), which no route answers");
    }
});

test('a QR logo upload answers with the page version, so the next save is not refused as a conflict', function () {
    Illuminate\Support\Facades\Storage::fake('public');
    $user = User::factory()->create();
    Hop\Bio\Models\BioPage::create(['user_id' => $user->id, 'slug' => 'qrlogo', 'title' => 'QR']);
    $this->actingAs($user);
    $this->travel(5)->seconds();
    $up = $this->postJson('/bio/qrlogo/upload-qr-logo', ['logo' => Illuminate\Http\UploadedFile::fake()->image('logo.png', 64, 64)])
        ->assertOk()->assertJsonPath('success', true);
    expect($up->json('logo_url'))->toStartWith('/storage/qr-logos/');
    expect($up->json('page_updated_at'))->toBeString();
    // the version the editor held before the upload is now stale: that save would be refused
    $this->putJson('/bio/qrlogo', ['title' => 'QR', 'base_updated_at' => now()->subSeconds(5)->toJSON()])->assertStatus(409);
    $this->travel(5)->seconds();
    $this->putJson('/bio/qrlogo', ['qr_settings' => ['image' => $up->json('logo_url')], 'base_updated_at' => $up->json('page_updated_at')])
        ->assertOk();
    $this->postJson('/bio/qrlogo/upload-qr-logo', ['logo' => Illuminate\Http\UploadedFile::fake()->create('notes.txt', 2, 'text/plain')])
        ->assertStatus(422)->assertJsonValidationErrors('logo');
});
