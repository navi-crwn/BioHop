<?php

use App\Models\AbuseFlag;
use App\Models\User;
use Hop\Bio\Jobs\PurgeEdgeCache;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Models\BioPollVote;
use Hop\Bio\Models\BioRevision;
use Hop\Ui\Locales;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

/*
| Manage: prices, stock and goal figures of every page of the member in one place, polls and forms to read.
| Who may do what, an editor save that comes first, and what the public page and the caches do afterwards.
*/

function manageSetup(string $slug = 'shop'): array
{
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => $slug, 'title' => 'Shop '.$slug, 'is_published' => true]);
    $mk = fn (string $type, array $extra) => BioLink::create(['bio_page_id' => $page->id, 'type' => $type, 'order' => 0, 'is_active' => true] + $extra);
    $shirt = $mk('product', ['title' => 'Linen shirt', 'url' => 'https://shop.example/shirt', 'settings' => ['price' => 120000, 'currency' => 'IDR', 'compare_price' => 150000, 'stock' => 8, 'variants' => [['name' => 'M', 'price' => 110000, 'stock' => 3]]]]);
    $goal = $mk('goal', ['title' => 'New camera', 'settings' => ['current' => 40, 'target' => 100, 'unit' => 'USD']]);
    $poll = $mk('poll', ['title' => 'Favourite colour', 'settings' => ['options' => [['text' => 'Blue'], ['text' => 'Green']]]]);
    $form = $mk('form', ['title' => 'Join the list', 'settings' => ['ask_name' => true, 'ask_email' => true]]);

    return [$user, $page, $shirt, $goal, $poll, $form];
}

function manageBody(BioLink $block, array $settings, array $extra = []): array
{
    $block = $block->fresh();
    $base = $block->type === 'product' ? ['title' => $block->title, 'is_active' => (bool) $block->is_active, 'settings' => $settings + ['sold_out' => (bool) ($block->settings['sold_out'] ?? false)]] : ['settings' => $settings];

    return array_replace(['base_version' => $block->contentVersion()] + $base, $extra);
}

test('who may do what: a guest goes to sign-in, a stranger and an unverified member are refused, the owner is served', function () {
    [$owner, , $shirt, $goal] = manageSetup();
    $this->get('/bio/manage')->assertRedirect();
    $this->patchJson("/bio/manage/{$shirt->id}", manageBody($shirt, ['price' => 1]))->assertStatus(401);

    $stranger = User::factory()->create();
    $page = $this->actingAs($stranger)->get('/bio/manage')->assertOk()->getContent();
    expect($page)->not->toContain('Linen shirt')->not->toContain('New camera')->not->toContain('Favourite colour');
    $this->patchJson("/bio/manage/{$shirt->id}", manageBody($shirt, ['price' => 1]))->assertNotFound();
    $this->patchJson("/bio/manage/{$goal->id}", manageBody($goal, ['current' => 1, 'target' => 2]))->assertNotFound();

    $unverified = User::factory()->unverified()->create();
    $this->actingAs($unverified)->patchJson("/bio/manage/{$shirt->id}", manageBody($shirt, ['price' => 1]))->assertForbidden();

    $fresh = $shirt->fresh();
    expect($fresh->settings['price'])->toBe(120000)->and($goal->fresh()->settings['current'])->toBe(40);

    $this->actingAs($owner)->get('/bio/manage')->assertOk()->assertSee('Linen shirt')->assertSee('New camera')->assertSee('Favourite colour')->assertSee('Join the list');
    $this->patchJson("/bio/manage/{$shirt->id}", manageBody($shirt, ['price' => 99000, 'stock' => 8, 'compare_price' => 150000]))->assertOk()->assertJsonPath('success', true);
});

test('only product and goal blocks can be changed here, and no other kind is reachable by id', function () {
    [$owner, , , , $poll, $form] = manageSetup();
    $this->actingAs($owner);
    foreach ([$poll, $form] as $block) {
        $this->patchJson("/bio/manage/{$block->id}", ['base_version' => $block->contentVersion(), 'settings' => ['price' => 1]])->assertNotFound();
    }
    $this->patchJson('/bio/manage/999999', ['base_version' => 'x', 'settings' => []])->assertNotFound();
});

test('a product change is merged into the block: variants and currency stay, the page is touched and the caches go', function () {
    [$owner, $page, $shirt] = manageSetup('merge');
    config(['services.cloudflare.zone_id' => 'zone123', 'services.cloudflare.api_token' => 'token123', 'app.url' => 'https://bio.hel.ink', 'hop.public_url' => 'https://hel.ink']);
    $this->actingAs($owner);
    Cache::put('bio-stateless:merge', true, 60);
    Queue::fake();
    $touched = $page->fresh()->updated_at;
    $this->travel(5)->seconds();

    $this->patchJson("/bio/manage/{$shirt->id}", manageBody($shirt, ['price' => '99000.50', 'compare_price' => '', 'stock' => '0'], ['title' => 'Linen shirt v2', 'is_active' => true]))->assertOk();

    $block = $shirt->fresh();
    expect($block->title)->toBe('Linen shirt v2')
        ->and($block->settings['price'])->toBe(99000.5)
        ->and($block->settings['stock'])->toBe(0)
        ->and($block->settings)->not->toHaveKey('compare_price')
        ->and($block->settings['currency'])->toBe('IDR')
        ->and($block->settings['variants'][0]['name'])->toBe('M')
        ->and($block->isSoldOut())->toBeTrue();
    expect($page->fresh()->updated_at->gt($touched))->toBeTrue();
    expect(Cache::has('bio-stateless:merge'))->toBeFalse();
    expect(BioRevision::where('bio_page_id', $page->id)->count())->toBe(1);
    Queue::assertPushed(PurgeEdgeCache::class);

    // the public page and the shop show the new figures
    $this->get('/b/merge')->assertOk()->assertSee('Linen shirt v2')->assertSee('Sold out');
});

test('hiding and showing a product and marking it sold out are saved, and the public page follows', function () {
    [$owner, , $shirt] = manageSetup('hide');
    $this->actingAs($owner)->patchJson("/bio/manage/{$shirt->id}", manageBody($shirt, ['price' => 120000], ['is_active' => false]))->assertOk();
    expect($shirt->fresh()->is_active)->toBeFalse();
    $this->get('/b/hide')->assertOk()->assertDontSee('Linen shirt');
    $shirt = $shirt->fresh();
    $this->patchJson("/bio/manage/{$shirt->id}", manageBody($shirt, ['price' => 120000, 'sold_out' => true], ['is_active' => true]))->assertOk();
    expect($shirt->fresh()->is_active)->toBeTrue()->and($shirt->fresh()->settings['sold_out'])->toBeTrue();
});

test('a goal keeps the rest of its settings and the public bar follows', function () {
    [$owner, , , $goal] = manageSetup('goal');
    $this->actingAs($owner)->patchJson("/bio/manage/{$goal->id}", manageBody($goal, ['current' => '75', 'target' => '150', 'unit' => 'EUR']))->assertOk();
    $g = $goal->fresh();
    expect($g->settings)->toMatchArray(['current' => 75, 'target' => 150, 'unit' => 'EUR'])->and($g->goalPercent())->toBe(50);
    $this->get('/b/goal')->assertOk()->assertSee('EUR');
});

test('invalid values are refused with 422 and nothing changes', function (array $settings, array $extra, string $field, string $type) {
    [$owner, , $shirt, $goal] = manageSetup('bad');
    $block = $type === 'product' ? $shirt : $goal;
    $before = $block->fresh()->getAttributes();
    $this->actingAs($owner)->patchJson("/bio/manage/{$block->id}", manageBody($block, $settings, $extra))->assertStatus(422)->assertJsonValidationErrors($field);
    expect($block->fresh()->getAttributes())->toBe($before);
})->with([
    'a negative price' => [['price' => -5], [], 'settings.price', 'product'],
    'a price that is not a number' => [['price' => 'free'], [], 'settings.price', 'product'],
    'stock that is not a number' => [['stock' => 'many'], [], 'settings.stock', 'product'],
    'a fractional stock' => [['stock' => '1.5'], [], 'settings.stock', 'product'],
    'a negative stock' => [['stock' => -1], [], 'settings.stock', 'product'],
    'a was-price that is not a number' => [['compare_price' => 'x'], [], 'settings.compare_price', 'product'],
    'an empty name' => [['price' => 1], ['title' => ''], 'title', 'product'],
    'a name that is far too long' => [['price' => 1], ['title' => str_repeat('a', 101)], 'title', 'product'],
    'a goal with nothing so far' => [['current' => '', 'target' => 10], [], 'settings.current', 'goal'],
    'a goal with an empty target' => [['current' => 1, 'target' => ''], [], 'settings.target', 'goal'],
    'a goal of zero' => [['current' => 1, 'target' => 0], [], 'settings.target', 'goal'],
    'a negative amount so far' => [['current' => -1, 'target' => 10], [], 'settings.current', 'goal'],
    'a unit that is too long' => [['current' => 1, 'target' => 10, 'unit' => str_repeat('u', 21)], [], 'settings.unit', 'goal'],
]);

test('a block that changed since the page was opened is not overwritten: 409', function () {
    [$owner, $page, $shirt, $goal] = manageSetup('conflict');
    $this->actingAs($owner);
    $body = manageBody($shirt, ['price' => 50000, 'stock' => 8, 'compare_price' => 150000]);

    // the editor saves the same block meanwhile
    $this->putJson("/bio/conflict/links/{$shirt->id}", ['title' => 'Linen shirt', 'settings' => ['price' => 130000, 'currency' => 'IDR']])->assertOk();
    $this->patchJson("/bio/manage/{$shirt->id}", $body)->assertStatus(409)->assertJsonPath('conflict', true);
    expect($shirt->fresh()->settings['price'])->toBe(130000);

    // a click on the block does not count as a change
    $goalBody = manageBody($goal, ['current' => 41, 'target' => 100]);
    $goal->increment('click_count');
    $this->patchJson("/bio/manage/{$goal->id}", $goalBody)->assertOk();

    // the version that comes back is the next base: a second save from the same row goes through
    $first = $this->patchJson("/bio/manage/{$goal->id}", manageBody($goal->fresh(), ['current' => 42, 'target' => 100]))->assertOk()->json('version');
    $this->patchJson("/bio/manage/{$goal->id}", ['base_version' => $first, 'settings' => ['current' => 43, 'target' => 100]])->assertOk();
    expect($goal->fresh()->settings['current'])->toBe(43);
});

test('the other direction: an editor that was open before keeps its copy of the block and replaces what Manage saved', function () {
    // The editor sends the whole block, like it always did. This pins that behaviour down so a change to it is noticed.
    [$owner, , $shirt] = manageSetup('reverse');
    $this->actingAs($owner)->patchJson("/bio/manage/{$shirt->id}", manageBody($shirt, ['price' => 50000, 'stock' => 8, 'compare_price' => 150000]))->assertOk();
    $this->putJson("/bio/reverse/links/{$shirt->id}", ['title' => 'Linen shirt', 'settings' => ['price' => 120000, 'currency' => 'IDR']])->assertOk();
    expect($shirt->fresh()->settings['price'])->toBe(120000);
});

test('a block the moderators switched off stays off', function () {
    [$owner, , $shirt] = manageSetup('locked');
    $shirt->update(['is_active' => false]);
    AbuseFlag::create(['subject_type' => 'bio_link', 'subject_id' => $shirt->id, 'user_id' => $owner->id, 'score' => 90, 'signals' => ['x'], 'action' => 'disabled', 'status' => 'open', 'summary' => 'x']);
    $this->actingAs($owner)->patchJson("/bio/manage/{$shirt->id}", manageBody($shirt->fresh(), ['price' => 1], ['is_active' => true]))->assertStatus(422);
    expect($shirt->fresh()->is_active)->toBeFalse();
});

test('polls and forms are shown to read: counts per option, entries and the CSV link, and nothing to edit', function () {
    [$owner, $page, , , $poll, $form] = manageSetup('read');
    foreach ([0, 0, 1] as $i => $option) {
        BioPollVote::create(['bio_link_id' => $poll->id, 'option_index' => $option, 'voter_hash' => 'h'.$i]);
    }
    $form->leads()->create(['name' => 'Rina', 'email' => 'rina@example.com']);
    $html = $this->actingAs($owner)->get('/bio/manage')->assertOk()->getContent();
    expect($html)->toContain('Blue')->toContain('67%')->toContain('3 votes')->toContain('1 entry')->toContain(route('bio.links.leads.csv', [$page, $form]))
        ->toContain('/bio/read/edit?tab=links');
    // the poll and form are not rows that can be saved
    expect(substr_count($html, 'data-manage-row="product"'))->toBe(1)->and(substr_count($html, 'data-manage-row="goal"'))->toBe(1);
});

test('the menu offers Manage and only one of the two entries is lit', function () {
    [$owner] = manageSetup('menu');
    $on = fn (string $html, string $label) => preg_match('~<a href="[^"]*" class="nav-item nav-item-active">\s*<svg[^>]*><path[^>]*/></svg>\s*<span class="flex-1">'.preg_quote($label, '~').'</span>~s', $html) === 1;
    $manage = $this->actingAs($owner)->get('/bio/manage')->assertOk()->getContent();
    expect($on($manage, 'Manage'))->toBeTrue()->and($on($manage, 'My bio pages'))->toBeFalse();
    $home = $this->get('/bio')->assertOk()->getContent();
    expect($on($home, 'My bio pages'))->toBeTrue()->and($on($home, 'Manage'))->toBeFalse();
});

test('a member with no products or goals gets a calm empty page', function () {
    $this->actingAs(User::factory()->create())->get('/bio/manage')->assertOk()->assertSee('No products, goals, polls or forms yet');
});

test('every word of the Manage page and the product form exists in all nine languages', function () {
    $src = file_get_contents(base_path('packages/bio/resources/views/manage.blade.php'))
        .file_get_contents(base_path('packages/bio/src/Http/Controllers/BioManageController.php'))
        .file_get_contents(base_path('resources/views/partials/nav.blade.php'));
    $editor = file_get_contents(base_path('packages/bio/resources/views/editor/index.blade.php'));
    $form = substr($editor, strpos($editor, "editingBlock?.type === 'product'"), strpos($editor, "editingBlock?.type === 'collection'") - strpos($editor, "editingBlock?.type === 'product'"));
    preg_match_all("/__\\(\\s*(['\"])((?:(?!\\1).|\\\\.)*)\\1/s", $src.$form, $m);
    $keys = array_unique(array_map(fn ($k) => stripcslashes($k), $m[2]));
    expect(count($keys))->toBeGreaterThan(40);
    foreach (Locales::codes() as $lang) {
        $d = json_decode(file_get_contents(base_path("lang/{$lang}.json")), true);
        foreach ($keys as $k) {
            expect(array_key_exists($k, $d))->toBeTrue("{$lang}: {$k}");
        }
    }
    // the product form of the editor is no longer plain English
    expect($form)->not->toContain('>Product name<')->not->toContain('>Where to buy (address)<');
});

test('the version the Manage page hands out is the one the server accepts, for a product and a goal', function () {
    [$owner, , $shirt, $goal] = manageSetup('handed');
    $html = $this->actingAs($owner)->get('/bio/manage')->assertOk()->getContent();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html, LIBXML_NOERROR);
    $handed = collect();
    foreach ((new DOMXPath($dom))->query('//form[@data-manage-row]') as $form) {
        // x-data="manageRow(JSON.parse('...'))": the quoted part is a JS string literal that holds the JSON
        preg_match("/^manageRow\(JSON\.parse\('(.*)'\)\)$/s", $form->getAttribute('x-data'), $literal);
        $cfg = json_decode(json_decode('"'.$literal[1].'"'), true);
        $handed[(int) basename($cfg['url'])] = $cfg['version'];
    }
    expect($handed->keys()->all())->toEqualCanonicalizing([$shirt->id, $goal->id]);
    // the page loads the blocks with their entry counts; that must not make the version differ from the one the save computes
    $this->patchJson("/bio/manage/{$shirt->id}", ['base_version' => $handed[$shirt->id], 'title' => 'Linen shirt', 'is_active' => true, 'settings' => ['price' => 99000, 'sold_out' => false]])->assertOk();
    $this->patchJson("/bio/manage/{$goal->id}", ['base_version' => $handed[$goal->id], 'settings' => ['current' => 50, 'target' => 100]])->assertOk();
    expect($shirt->fresh()->settings['price'])->toBe(99000)->and($goal->fresh()->settings['current'])->toBe(50);
});
