<?php

use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;

/*
| Drag to reorder in the editor (B-1, A-5). The blocks list held a <style> and the select bar before its blocks, and
| Sortable's oldIndex/newIndex count those too: used as array positions they moved the wrong block, or put an empty
| entry in the list ("Untitled"), threw inside Sortable's end event and froze every later drag and save. Now the list
| holds only its blocks, the new order is read from the items' ids (resources/js/drop-move.js), nothing thrown escapes
| the drop, and the drag clone stays inside the editor so it keeps the block's look.
*/

test('the drop logic of the editor lists passes its JS tests', function () {
    $node = trim((string) shell_exec('command -v node 2>/dev/null'));
    if ($node === '') {
        $this->markTestSkipped('node is not installed');
    }
    exec(escapeshellarg($node).' --test '.escapeshellarg(base_path('tests/js/drop-move.test.mjs')).' 2>&1', $out, $code);
    expect($code)->toBe(0, implode("\n", $out));
});

test('the sortable lists hold nothing but the x-for template and items with an id', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'drag-list', 'title' => 'Drag', 'social_links' => [['platform' => 'instagram', 'url' => 'https://instagram.com/x']]]);
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'One', 'url' => 'https://example.com/1', 'order' => 0]);
    $html = $this->actingAs($user)->get('/bio/drag-list/edit')->assertOk()->getContent();

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
    foreach (['blocks-list' => ':data-id="item.id"', 'socials-list' => ':data-id="item._uid"'] as $id => $dataId) {
        $list = $dom->getElementById($id);
        expect($list)->not->toBeNull($id);
        $children = [];
        foreach ($list->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $children[] = $child->tagName;
            }
        }
        expect($children)->toBe(['template'], $id); // the items are rendered by Alpine from this template only
        expect($dom->saveHTML($list))->toContain($dataId);
    }
});

test('the editor reorders by ids, keeps the drag clone inside the editor and never lets a drop throw', function () {
    $js = file_get_contents(resource_path('js/editor.js'));
    expect($js)->toContain("import { orderByIds, itemIds, putBack } from './drop-move.js';")
        ->toContain('orderByIds(Alpine.raw(this.blocks), ids)')
        ->toContain('orderByIds(Alpine.raw(this.bioPage.social_links || []), ids, s => s?._uid)')
        ->not->toContain('fallbackOnBody: true')
        ->not->toContain('onMove(oldIndex, newIndex)')
        ->not->toContain('document.body._x_ignoreMutationObserver');
    $makeSortable = substr($js, strpos($js, 'makeSortable(el, handle, onDrop)'), 2500);
    expect($makeSortable)->toContain('try {')->toContain('} catch (error) {');
});

test('the reorder endpoint stores the order it is sent, for the page\'s own blocks only', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'drag-save', 'title' => 'Drag']);
    $ids = collect(['A', 'B', 'C'])->map(fn ($t, $i) => BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => $t, 'url' => 'https://example.com/'.$t, 'order' => $i])->id);
    $other = BioLink::create(['bio_page_id' => BioPage::create(['user_id' => User::factory()->create()->id, 'slug' => 'not-mine', 'title' => 'X'])->id, 'type' => 'link', 'title' => 'X', 'url' => 'https://example.com/x', 'order' => 0]);

    $this->actingAs($user)->postJson('/bio/drag-save/links/reorder', ['links' => [['id' => $ids[2], 'order' => 0], ['id' => $ids[0], 'order' => 1], ['id' => $ids[1], 'order' => 2]]])->assertOk();
    expect($page->links()->orderBy('order')->pluck('title')->all())->toBe(['C', 'A', 'B']);
    $this->actingAs($user)->postJson('/bio/drag-save/links/reorder', ['links' => [['id' => $other->id, 'order' => 5]]]);
    expect($other->fresh()->order)->toBe(0);
    $this->get('/b/drag-save')->assertOk()->assertSeeInOrder(['C', 'A', 'B']);
});
