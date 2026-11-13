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
    exec(escapeshellarg($node).' --test '.escapeshellarg(base_path('tests/js/drop-move.test.mjs')).' '.escapeshellarg(base_path('tests/js/editor-safe.test.mjs')).' 2>&1', $out, $code);
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

test('the editor wires its lists to the id-based drop, keyed so that a new id cannot swap an element under a finger', function () {
    $js = file_get_contents(resource_path('js/editor.js'));
    // The behaviour itself is tested in tests/js (finishDrop, orderByIds, dropStray); this only checks that the editor
    // uses it and has not gone back to positions.
    expect($js)->toContain('finishDrop(el, evt, onDrop)')->toContain('orderByIds(raw, ids)')
        ->not->toMatch('/(?:evt|event)\.(?:old|new)Index/')
        ->not->toContain('fallbackOnBody: true')
        ->not->toContain('document.body._x_ignoreMutationObserver');
    // createBlock swaps the temporary id for the real one: the list key must not follow it (H-1)
    expect($js)->toMatch('/block\._key \?\?= block\.id;\s*block\.id = /');
    expect(file_get_contents(base_path('packages/bio/resources/views/editor/links-tab.blade.php')))->toContain(':key="item._key ?? item.id"');
    // every message to the preview goes through the guard that can resend a plain copy (S-1)
    expect($js)->toContain('postToPreview(frame.contentWindow')->not->toMatch('/contentWindow\.postMessage\(\{ type: .preview-update/');
});

test('the reorder endpoint stores the order it is sent, for the page\'s own blocks only', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'drag-save', 'title' => 'Drag']);
    $ids = collect(['Alpha-one', 'Bravo-two', 'Charlie-three'])->map(fn ($t, $i) => BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => $t, 'url' => 'https://example.com/'.$t, 'order' => $i])->id);
    $other = BioLink::create(['bio_page_id' => BioPage::create(['user_id' => User::factory()->create()->id, 'slug' => 'not-mine', 'title' => 'X'])->id, 'type' => 'link', 'title' => 'X', 'url' => 'https://example.com/x', 'order' => 0]);

    $this->actingAs($user)->postJson('/bio/drag-save/links/reorder', ['links' => [['id' => $ids[2], 'order' => 0], ['id' => $ids[0], 'order' => 1], ['id' => $ids[1], 'order' => 2]]])->assertOk();
    expect($page->links()->orderBy('order')->pluck('title')->all())->toBe(['Charlie-three', 'Alpha-one', 'Bravo-two']);
    $this->actingAs($user)->postJson('/bio/drag-save/links/reorder', ['links' => [['id' => $other->id, 'order' => 5]]]);
    expect($other->fresh()->order)->toBe(0);
    $html = $this->get('/b/drag-save')->assertOk()->getContent();
    $at = collect(['Charlie-three', 'Alpha-one', 'Bravo-two'])->map(fn ($t) => strpos($html, '>'.$t.'<') ?: strpos($html, $t));
    expect($at->every(fn ($p) => $p !== false))->toBeTrue();
    expect($at->all())->toBe($at->sort()->values()->all()); // the public page lists them in the saved order
    $this->actingAs($user)->postJson('/bio/drag-save/links/reorder', ['links' => [['id' => $ids[0], 'order' => 0], ['id' => $ids[1], 'order' => 1], ['id' => $ids[2], 'order' => 2]]])->assertOk();
    $back = $this->get('/b/drag-save')->getContent();
    expect(strpos($back, 'Alpha-one'))->toBeLessThan(strpos($back, 'Bravo-two'))->and(strpos($back, 'Bravo-two'))->toBeLessThan(strpos($back, 'Charlie-three'));
});

test('the editor messages of the reorder and upload fixes exist in all nine languages and reach the page', function () {
    $keys = [
        'The file is too large.',
        'Upload failed',
        ':name moved to position :position of :total',
        'Could not save the new order. Click Save to retry.',
        'Please wait for the current save, then upload the logo.',
        'Failed to upload: :reason',
        'Failed to upload image: :reason',
        'Unknown error',
    ];
    foreach (['zh-CN', 'de', 'id', 'ja', 'pt-BR', 'es', 'fr', 'ru'] as $locale) {
        $lang = json_decode(file_get_contents(lang_path($locale.'.json')), true);
        foreach ($keys as $key) {
            expect($lang)->toHaveKey($key);
            expect($lang[$key])->not->toBe($key);
            preg_match_all('/:\w+/', $key, $want);
            preg_match_all('/:\w+/', $lang[$key], $got);
            expect(collect($got[0])->sort()->values()->all())->toBe(collect($want[0])->sort()->values()->all());
        }
    }

    // the ones the editor's config carries (editor/index.blade.php); the logo upload texts sit in the profile tab's script
    $inConfig = [$keys[0], $keys[1], $keys[2], $keys[3], $keys[6]];
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'words-page', 'title' => 'Words']);
    $id = json_decode(file_get_contents(lang_path('id.json')), true);
    $html = $this->actingAs($user)->get('/bio/words-page/edit?lang=id')->assertOk()->getContent();
    foreach ($inConfig as $key) {
        expect($html)->toContain(json_encode($id[$key], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
    foreach ([$keys[4], $keys[5], $keys[7]] as $key) {
        expect($html)->toContain("'".$id[$key]."'"); // @js(__()) in the profile tab prints a single-quoted string
    }
});
