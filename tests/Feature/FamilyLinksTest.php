<?php

use App\Bio\FamilyUrls;
use App\Bio\LinkHopClient;
use App\Bio\LinkHopShortlinks;
use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('links to hel.ink and the HOP apps are family links, others are not', function () {
    foreach (['https://hel.ink/b/hel', 'https://note.hel.ink/abc#k=1', 'https://pixel.hel.ink/', 'https://p.hel.ink/x.png', 'https://link.hel.ink/l/launch', 'https://bio.hel.ink/', 'https://hub.hel.ink/dashboard'] as $url) {
        expect(FamilyUrls::isFamily($url))->toBeTrue($url);
    }
    foreach (['https://example.com/', 'https://hel.ink.evil.test/', 'https://nothel.ink/', 'mailto:me@hel.ink', 'not a url'] as $url) {
        expect(FamilyUrls::isFamily($url))->toBeFalse($url);
    }
});

test('a family link is returned as typed and the shortener is not called', function () {
    $client = Mockery::mock(LinkHopClient::class);
    $client->shouldNotReceive('shorten');
    $result = (new LinkHopShortlinks($client))->findOrCreate(User::factory()->create(), 'https://note.hel.ink/abc', 'Notes');
    expect($result['short_url'])->toBe('https://note.hel.ink/abc')->and($result['family'])->toBeTrue();
});

test('the editor is given the family hosts', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'famhosts', 'title' => 'F']);
    $html = $this->actingAs($user)->get('/bio/famhosts/edit')->assertOk()->getContent();
    preg_match('/id="bio-editor-config">(.*?)<\/script>/s', $html, $m);
    expect(json_decode($m[1], true)['familyHosts'])->toContain('hel.ink');
});

test('the unwrap command restores family targets and leaves other short links alone', function () {
    Schema::create('links', function ($table) {
        $table->id();
        $table->string('slug');
        $table->string('target_url');
    });
    DB::table('links')->insert([['slug' => 'nt1', 'target_url' => 'https://note.hel.ink/abc'], ['slug' => 'ex1', 'target_url' => 'https://example.com/x']]);
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'unwrap', 'title' => 'U']);
    $note = BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Note', 'url' => 'https://hel.ink/l/nt1', 'order' => 0, 'is_active' => true]);
    $other = BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Other', 'url' => 'https://hel.ink/l/ex1', 'order' => 1, 'is_active' => true]);
    $this->artisan('bio:unwrap-family-links')->assertSuccessful();
    expect($note->fresh()->url)->toBe('https://hel.ink/l/nt1'); // a dry run writes nothing
    $this->artisan('bio:unwrap-family-links --apply')->assertSuccessful();
    expect($note->fresh()->url)->toBe('https://note.hel.ink/abc')->and($other->fresh()->url)->toBe('https://hel.ink/l/ex1');
});
