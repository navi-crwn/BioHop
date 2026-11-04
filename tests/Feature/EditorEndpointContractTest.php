<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

test('the existing-link picker asks the registered search route, which answers a list', function () {
    Http::fake(['https://link.test/api/links*' => Http::response(['data' => [
        ['id' => 1, 'title' => 'Demo', 'url' => 'https://example.com/page', 'short_url' => 'https://link.test/l/demo'],
    ]])]);

    $this->actingAs(User::factory()->create())
        ->getJson('/bio/links/search?query=demo')
        ->assertOk()
        ->assertJsonPath('0.short_url', 'https://link.test/l/demo');

    expect(file_get_contents(base_path('resources/js/editor.js')))
        ->toContain("fetch('/bio/links/search?query=' + encodeURIComponent(this.librarySearch)")
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
