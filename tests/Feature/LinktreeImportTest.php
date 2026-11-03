<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Support\LinktreeImport;
use Hop\Safety\UrlGuard;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // no real DNS in tests: private names resolve to a private address, "dual.example" to one v4 and one v6, the rest to a public v4
    app()->bind(UrlGuard::class, fn () => new class extends UrlGuard
    {
        protected function resolveHost(string $host): array
        {
            if (filter_var($host, FILTER_VALIDATE_IP)) {
                return [$host];
            }

            return match ($host) {
                'internal.example' => ['10.0.0.5'],
                'dual.example' => ['93.184.216.34', '2606:2800:220:1::1'],
                default => ['93.184.216.34'],
            };
        }
    });
});

test('a Linktree address in any spelling gives the username', function () {
    expect(LinktreeImport::username('https://linktr.ee/helena?utm=1'))->toBe('helena')
        ->and(LinktreeImport::username('linktr.ee/helena.studio'))->toBe('helena.studio')
        ->and(LinktreeImport::username('@helena'))->toBe('helena')
        ->and(LinktreeImport::username('https://example.com/helena'))->toBeNull();
});

test('a public Linktree page becomes a draft page with links, a header, a collection, socials and the picture', function () {
    Storage::fake('public');
    Http::fake([
        'linktr.ee/helenastudio' => Http::response(file_get_contents(base_path('tests/Fixtures/linktree-page.html'))),
        'ugc.production.linktr.ee/abc_helena.png' => Http::response("\x89PNG fake", 200, ['Content-Type' => 'image/png']),
    ]);
    $user = User::factory()->create();
    $this->actingAs($user)->get('/bio/import')->assertOk()->assertSee('Your Linktree address');
    $response = $this->post('/bio/import', ['linktree' => 'linktr.ee/helenastudio', 'slug' => 'helenaimport']);
    $page = BioPage::where('slug', 'helenaimport')->firstOrFail();
    $response->assertRedirect(route('bio.edit', $page));
    expect($page->title)->toBe('Helena Studio')->and($page->bio)->toBe('Illustrator & motion designer in Bandung.')
        ->and($page->is_published)->toBeFalse()->and($page->avatar_url)->toStartWith('avatars/');
    Storage::disk('public')->assertExists($page->avatar_url);
    $blocks = $page->links()->orderBy('order')->get();
    expect($blocks->pluck('type')->all())->toBe(['link', 'header', 'collection', 'link', 'link', 'link', 'link'])
        ->and($blocks->pluck('title')->all())->toBe(['Portfolio', 'Watch', 'Prints', 'Tote bag', 'Linen shirt', 'My channel', 'Madeup'])
        ->and($blocks[0]->thumbnail_url)->toBe('https://ugc.production.linktr.ee/thumb1.jpg')
        ->and($blocks[4]->thumbnail_url)->toBeNull()
        ->and($blocks[5]->brand)->toBe('youtube');
    expect(collect($page->social_links)->pluck('platform')->all())->toBe(['instagram', 'x', 'email']);
    expect(session('success'))->toContain('7 blocks')->toContain('3 social icons');
});

test('a missing, locked or non-Linktree page is reported without creating anything', function () {
    Http::fake([
        'linktr.ee/nobody' => Http::response('', 404),
        'linktr.ee/locked' => Http::response('<script id="__NEXT_DATA__" type="application/json">{"props":{"pageProps":{"hasPagePassword":true,"links":[]}}}</script>'),
    ]);
    $user = User::factory()->create();
    $this->actingAs($user)->post('/bio/import', ['linktree' => 'linktr.ee/nobody', 'slug' => 'nobody1'])
        ->assertSessionHasErrors(['linktree' => 'There is no Linktree page with that name.']);
    $this->post('/bio/import', ['linktree' => 'linktr.ee/locked', 'slug' => 'locked1'])->assertSessionHasErrors('linktree');
    $this->post('/bio/import', ['linktree' => 'https://example.com/x', 'slug' => 'other1'])->assertSessionHasErrors('linktree');
    expect(BioPage::count())->toBe(0);
});

test('a page redirect to a private address is not followed', function () {
    Http::fakeSequence()
        ->push('', 302, ['Location' => 'http://127.0.0.1:8530/private'])
        ->push('<script id="__NEXT_DATA__">{"props":{"pageProps":{}}}</script>', 200);

    expect(fn () => LinktreeImport::fetch('example'))->toThrow(RuntimeException::class);
    Http::assertSentCount(1);
    Http::assertNotSent(fn (Request $request) => parse_url($request->url(), PHP_URL_HOST) === '127.0.0.1');
});

test('a page redirect to a name that resolves to a private address is not followed', function () {
    Http::fakeSequence()
        ->push('', 301, ['Location' => 'https://internal.example/x'])
        ->push('<script id="__NEXT_DATA__">{"props":{"pageProps":{}}}</script>', 200);

    expect(fn () => LinktreeImport::fetch('example'))->toThrow(RuntimeException::class);
    Http::assertSentCount(1);
});

test('a profile picture redirect to a private address is not followed', function () {
    Storage::fake('public');
    Http::fakeSequence()
        ->push('', 302, ['Location' => 'http://127.0.0.1:8530/private.png'])
        ->push('not-an-image', 200, ['Content-Type' => 'image/png']);

    expect(LinktreeImport::storePicture('https://ugc.production.linktr.ee/avatar.png'))->toBeNull();
    Http::assertSentCount(1);
    Http::assertNotSent(fn (Request $request) => parse_url($request->url(), PHP_URL_HOST) === '127.0.0.1');
});

test('a redirect to another public address is followed once the address is checked, and a loop stops', function () {
    Http::fakeSequence()
        ->push('', 302, ['Location' => 'https://www.linktr.ee/helena'])
        ->push(file_get_contents(base_path('tests/Fixtures/linktree-page.html')), 200);
    expect(LinktreeImport::fetch('helena')['title'])->toBe('Helena Studio');

    Http::fake(['*' => Http::response('', 302, ['Location' => 'https://linktr.ee/loop'])]);
    expect(fn () => LinktreeImport::fetch('loop'))->toThrow(RuntimeException::class);
});

test('the connection is pinned to the checked addresses in one entry per host, so a v4-only server still connects', function () {
    $pins = [];
    Http::fake(function (Request $request, array $options) use (&$pins) {
        $pins[] = $options['curl'][CURLOPT_RESOLVE] ?? null;

        return Http::response('', 302, ['Location' => 'https://dual.example/next']);
    });

    expect(fn () => LinktreeImport::fetch('helena'))->toThrow(RuntimeException::class);
    expect($pins)->toBe([['linktr.ee:443:93.184.216.34'], ['dual.example:443:93.184.216.34,[2606:2800:220:1::1]'], ['dual.example:443:93.184.216.34,[2606:2800:220:1::1]']]);
});

test('the page fetch does not follow redirects by itself', function () {
    $seen = [];
    Http::fake(function (Request $request, array $options) use (&$seen) {
        $seen[] = $options['allow_redirects'] ?? null;

        return Http::response(file_get_contents(base_path('tests/Fixtures/linktree-page.html')));
    });

    LinktreeImport::fetch('helena');
    expect($seen)->toBe([false]);
});
