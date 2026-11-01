<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Support\LinktreeImport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

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
    $this->actingAs($user)->post('/bio/import', ['linktree' => 'linktr.ee/nobody', 'slug' => 'nobody1'])->assertSessionHasErrors('linktree');
    $this->post('/bio/import', ['linktree' => 'linktr.ee/locked', 'slug' => 'locked1'])->assertSessionHasErrors('linktree');
    $this->post('/bio/import', ['linktree' => 'https://example.com/x', 'slug' => 'other1'])->assertSessionHasErrors('linktree');
    expect(BioPage::count())->toBe(0);
});
