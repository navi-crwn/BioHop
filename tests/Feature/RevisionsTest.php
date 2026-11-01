<?php

use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Models\BioRevision;
use Hop\Bio\Support\BioRevisions;

function revPage(string $slug): array
{
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => $slug, 'title' => 'Original', 'bio' => 'First bio', 'theme' => 'sunset', 'is_published' => true]);
    $keep = BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Keep me', 'url' => 'https://a.example/', 'order' => 0, 'is_active' => true, 'click_count' => 5]);
    $gone = BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Will be deleted', 'url' => 'https://b.example/', 'order' => 1, 'is_active' => true]);

    return [$user, $page, $keep, $gone];
}

test('an edit takes an automatic version first, then at most one per quarter of an hour', function () {
    [$user, $page] = revPage('rauto');
    $this->actingAs($user)->putJson('/bio/rauto', ['title' => 'Changed once'])->assertOk();
    $this->putJson('/bio/rauto', ['title' => 'Changed twice'])->assertOk();
    expect($page->revisions()->count())->toBe(1);
    expect(json_decode($page->revisions()->first()->snapshot, true)['page']['title'])->toBe('Original');
    $this->travel(16)->minutes();
    $this->postJson("/bio/rauto/links/{$page->links()->first()->id}/toggle")->assertOk();
    expect($page->revisions()->count())->toBe(2);
});

test('restoring brings back design, text and blocks, keeps clicks, archives newer blocks and can itself be undone', function () {
    [$user, $page, $keep, $gone] = revPage('rrest');
    $version = BioRevisions::snapshot($page, 'Before the sale');
    // later: edit the page, change a block, delete one, add one
    $page->update(['title' => 'Sale!', 'bio' => 'Everything 50%', 'theme' => 'galaxy', 'slug' => 'rrest', 'is_published' => false]);
    $keep->update(['title' => 'Sale link', 'click_count' => 9]);
    $gone->delete();
    $added = BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Flash', 'url' => 'https://c.example/', 'order' => 2, 'is_active' => true]);

    $this->actingAs($user)->postJson("/bio/rrest/revisions/{$version->id}/restore")->assertOk();
    $page->refresh();
    expect($page->title)->toBe('Original')->and($page->bio)->toBe('First bio')->and($page->theme)->toBe('sunset')
        ->and($page->is_published)->toBeFalse()->and($page->slug)->toBe('rrest');
    expect($keep->fresh()->title)->toBe('Keep me')->and($keep->fresh()->click_count)->toBe(9);
    expect($page->links()->where('title', 'Will be deleted')->exists())->toBeTrue();
    expect($added->fresh()->archived_at)->not->toBeNull();
    $before = $page->revisions()->where('label', 'like', 'Before restoring%')->first();
    expect($before)->not->toBeNull();
    // undo the restore
    $this->postJson("/bio/rrest/revisions/{$before->id}/restore")->assertOk();
    expect($page->fresh()->title)->toBe('Sale!')->and($added->fresh()->archived_at)->toBeNull();
});

test('versions are listed and saved by name for the owner only, and only the last 30 are kept', function () {
    [$user, $page] = revPage('rlist');
    $this->actingAs($user)->postJson('/bio/rlist/revisions', ['label' => 'Launch day'])->assertOk();
    $this->getJson('/bio/rlist/revisions')->assertOk()->assertJsonFragment(['label' => 'Launch day'])->assertJsonMissing(['snapshot']);
    foreach (range(1, 32) as $i) {
        BioRevisions::snapshot($page, "v{$i}");
    }
    expect($page->revisions()->count())->toBe(BioRevisions::KEEP);
    $other = User::factory()->create();
    $this->actingAs($other)->getJson('/bio/rlist/revisions')->assertForbidden();
    $rev = BioRevision::first();
    $this->postJson("/bio/rlist/revisions/{$rev->id}/restore")->assertForbidden();
});
