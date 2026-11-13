<?php

use App\Models\User;
use App\Support\BioLimits;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Illuminate\Support\Facades\DB;

/*
| The endpoint that stores a block order (train 6, B-1 to B-3, from the train-4 audit C-1, C-2, C-3, C-5): it answers
| the same for a block of someone else and for one that does not exist, bounds its input, writes in one transaction,
| is throttled, checks the page revision when the request carries one, and a null in the social icons no longer
| stops the editor.
*/

function dragPage(string $slug = 'drag-hard', int $blocks = 3): array
{
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => $slug, 'title' => 'Drag']);
    $ids = collect(range(0, $blocks - 1))->map(fn ($i) => BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'B'.$i, 'url' => 'https://example.com/'.$i, 'order' => $i])->id)->all();

    return [$user, $page, $ids];
}

function ordersOf(BioPage $page): array
{
    return BioLink::where('bio_page_id', $page->id)->orderBy('id')->pluck('order', 'id')->all();
}

test('another member\'s block id and an id that does not exist get the very same answer, and nothing changes', function () {
    [$user, $page, $ids] = dragPage('drag-same');
    [, $otherPage, $otherIds] = dragPage('drag-theirs', 1);
    $before = ordersOf($page);
    $theirsBefore = ordersOf($otherPage);

    $mine = fn ($extra) => ['links' => [['id' => $ids[0], 'order' => 1], ['id' => $extra, 'order' => 0]]];
    $foreign = $this->actingAs($user)->postJson('/bio/drag-same/links/reorder', $mine($otherIds[0]));
    $missing = $this->actingAs($user)->postJson('/bio/drag-same/links/reorder', $mine(987654));

    expect($foreign->status())->toBe(422)->and($missing->status())->toBe(422);
    expect($foreign->getContent())->toBe($missing->getContent());
    expect(ordersOf($page))->toBe($before)->and(ordersOf($otherPage))->toBe($theirsBefore); // not even the member's own block moved
});

test('a visitor, an unverified member and a member who does not own the page are all turned away', function () {
    [$owner, $page, $ids] = dragPage('drag-attack');
    $before = ordersOf($page);
    $body = ['links' => [['id' => $ids[0], 'order' => 2]]];

    $this->postJson('/bio/drag-attack/links/reorder', $body)->assertStatus(401);
    $stranger = User::factory()->create();
    $this->actingAs($stranger)->postJson('/bio/drag-attack/links/reorder', $body)->assertForbidden();
    $unverified = User::factory()->unverified()->create();
    $unverified->bioPages()->create(['slug' => 'drag-unverified', 'title' => 'U']);
    $this->actingAs($unverified)->postJson('/bio/drag-unverified/links/reorder', $body)->assertStatus(403);
    expect(ordersOf($page))->toBe($before);
});

test('the list has a length limit, ids may not repeat, and an order has an upper bound', function () {
    [$user, $page, $ids] = dragPage('drag-bounds');
    $before = ordersOf($page);
    $url = '/bio/drag-bounds/links/reorder';
    $cap = max(BioLimits::maxBlocks(), count($ids));

    $this->actingAs($user)->postJson($url, ['links' => [['id' => $ids[0], 'order' => 0], ['id' => $ids[0], 'order' => 1]]])->assertStatus(422);
    $this->postJson($url, ['links' => array_fill(0, $cap + 1, ['id' => $ids[0], 'order' => 0])])->assertStatus(422);
    $this->postJson($url, ['links' => [['id' => $ids[0], 'order' => $cap]]])->assertStatus(422);
    $this->postJson($url, ['links' => [['id' => $ids[0], 'order' => 2147483647]]])->assertStatus(422);
    $this->postJson($url, ['links' => [['id' => 'abc', 'order' => 0]]])->assertStatus(422);
    $this->postJson($url, ['links' => [['id' => [$ids[0]], 'order' => 0]]])->assertStatus(422);
    expect(ordersOf($page))->toBe($before);
    // what the editor sends (every saved block, order 0..n-1) goes through
    $this->postJson($url, ['links' => [['id' => $ids[2], 'order' => 0], ['id' => $ids[0], 'order' => 1], ['id' => $ids[1], 'order' => 2]]])->assertOk();
    expect($page->links()->pluck('id')->all())->toBe([$ids[2], $ids[0], $ids[1]]);
});

test('the whole new order is written in one transaction', function () {
    [$user, $page, $ids] = dragPage('drag-atomic');
    $before = ordersOf($page);
    $updates = 0;
    DB::listen(function ($query) use (&$updates) {
        if (str_starts_with(strtolower($query->sql), 'update "bio_links"') && ++$updates === 2) {
            throw new RuntimeException('the database went away');
        }
    });
    $this->withoutExceptionHandling();
    try {
        $this->actingAs($user)->postJson('/bio/drag-atomic/links/reorder', ['links' => [['id' => $ids[0], 'order' => 2], ['id' => $ids[1], 'order' => 1], ['id' => $ids[2], 'order' => 0]]]);
    } catch (RuntimeException) {
    }
    expect($updates)->toBeGreaterThanOrEqual(2)->and(ordersOf($page))->toBe($before);
});

test('the route is throttled like the other editor routes', function () {
    [$user, , $ids] = dragPage('drag-throttle', 1);
    $this->actingAs($user);
    $codes = [];
    for ($i = 0; $i < 61; $i++) {
        $codes[] = $this->postJson('/bio/drag-throttle/links/reorder', ['links' => [['id' => $ids[0], 'order' => 0]]])->status();
    }
    expect(array_slice($codes, 0, 60))->each->toBe(200);
    expect($codes[60])->toBe(429);
});

test('blocks with the same order keep a fixed order, by id, wherever the blocks are read', function () {
    [, $page] = dragPage('drag-ties');
    expect($page->links()->toSql())->toContain('"order" asc, "id" asc');
    expect(BioLink::ordered()->toSql())->toContain('"order" asc, "id" asc');
    $ties = BioLink::where('bio_page_id', $page->id)->update(['order' => 4]);
    expect($page->fresh()->links->pluck('id')->all())->toBe(BioLink::where('bio_page_id', $page->id)->orderBy('id')->pluck('id')->all());
    // the editor's own load and the preview
    $user = $page->user;
    $this->actingAs($user)->get('/bio/drag-ties/edit')->assertOk();
});

test('a request that carries the page revision is refused with 409 when the page changed in another tab', function () {
    [$user, $page, $ids] = dragPage('drag-409');
    $base = $page->updated_at->toJSON();
    $body = fn (array $more = []) => ['links' => [['id' => $ids[1], 'order' => 0], ['id' => $ids[0], 'order' => 1]]] + $more;
    $before = ordersOf($page);

    DB::table('bio_pages')->where('id', $page->id)->update(['updated_at' => now()->addMinutes(5)]);
    $stale = $this->actingAs($user)->postJson('/bio/drag-409/links/reorder', $body(['base_updated_at' => $base]));
    $stale->assertStatus(409)->assertJsonPath('conflict', true);
    expect(ordersOf($page))->toBe($before);

    $fresh = $page->fresh()->updated_at->toJSON();
    $this->postJson('/bio/drag-409/links/reorder', $body(['base_updated_at' => $fresh]))->assertOk();
    expect(ordersOf($page))->not->toBe($before);
    // the editor of today does not send it yet: that keeps working
    $this->postJson('/bio/drag-409/links/reorder', $body())->assertOk();
});

test('a null social icon is refused on save and left out when the page is read', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'social-null', 'title' => 'S']);
    $this->actingAs($user);
    $this->putJson('/bio/social-null', ['social_links' => [null, ['platform' => 'instagram', 'value' => 'x', 'enabled' => true]]])
        ->assertStatus(422)->assertJsonValidationErrors('social_links.0');
    $this->putJson('/bio/social-null', ['social_links' => [['platform' => 'instagram', 'value' => 'x', 'enabled' => true]]])->assertOk();

    // data saved before the check: a null (and a stray string) inside the stored list
    DB::table('bio_pages')->where('id', $page->id)->update(['social_links' => json_encode([null, ['platform' => 'instagram', 'value' => 'x'], 'junk', ['platform' => 'x', 'value' => 'y']])]);
    $links = $page->fresh()->social_links;
    expect($links)->toHaveCount(2)->and($links[0]['platform'])->toBe('instagram')->and($links[1]['platform'])->toBe('x');
    expect(array_is_list($links))->toBeTrue();
    // the editor receives the cleaned list
    $html = $this->get('/bio/social-null/edit')->assertOk()->getContent();
    expect($html)->not->toContain('"social_links":[null');
    DB::table('bio_pages')->where('id', $page->id)->update(['social_links' => null]);
    expect($page->fresh()->social_links)->toBeNull();
});
