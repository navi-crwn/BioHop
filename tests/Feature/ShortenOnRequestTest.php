<?php

use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Illuminate\Support\Facades\Http;

/**
 * A short link is made only when the member presses "Shorten this URL" (report 2026-11-08, bio-ss-07): saving a
 * block keeps the address as typed, and the editor's source holds no automatic shortening any more.
 */
test('saving a link block keeps the address as typed, without asking LinkHop', function () {
    Http::fake();
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'astyped', 'title' => 'As typed', 'is_published' => true]);
    $res = $this->actingAs($user)->postJson('/bio/astyped/links', ['type' => 'link', 'title' => 'Portfolio', 'url' => 'https://example.com/portfolio?utm=x'])->assertOk()->json();
    $block = BioLink::findOrFail($res['block']['id']);
    $this->putJson("/bio/astyped/links/{$block->id}", ['title' => 'Portfolio (new title)'])->assertOk();
    expect($block->fresh()->url)->toBe('https://example.com/portfolio?utm=x')
        ->and($block->fresh()->title)->toBe('Portfolio (new title)');
    Http::assertNothingSent();
});

test('the editor offers "Shorten this URL" as a button and no longer promises automatic shortening', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'shortbtn', 'title' => 'Short']);
    $html = $this->actingAs($user)->get('/bio/shortbtn/edit')->assertOk()->getContent();
    expect($html)->toContain('data-shorten-url')->toContain('Shorten this URL')->toContain('shortenEditingUrl()')
        ->not->toContain('automatically shorten');
    $js = file_get_contents(base_path('resources/js/editor.js'));
    expect($js)->toContain('shortenEditingUrl()')->not->toContain('Auto-shorten');
});
