// Run: node --test tests/js/drop-move.test.mjs   (Pest runs it too: tests/Feature/EditorReorderTest.php)
import test from 'node:test';
import assert from 'node:assert/strict';
import { orderByIds, itemIds, putBack, sameOrder, dropStray, finishDrop } from '../../resources/js/drop-move.js';

const blocks = () => [{ id: 11, type: 'link' }, { id: 12, type: 'pay' }, { id: 13, type: 'app' }, { id: 14, type: 'text' }];
const types = (list) => list.map((b) => b.type);

test('the new order follows the ids in the DOM: top to bottom, bottom to top, one step', () => {
    assert.deepEqual(types(orderByIds(blocks(), ['12', '13', '14', '11'])), ['pay', 'app', 'text', 'link']);
    assert.deepEqual(types(orderByIds(blocks(), ['14', '11', '12', '13'])), ['text', 'link', 'pay', 'app']);
    assert.deepEqual(types(orderByIds(blocks(), ['12', '11', '13', '14'])), ['pay', 'link', 'app', 'text']);
});

test('the same entries come back, never an empty one, and ids may be numbers or strings', () => {
    const list = blocks();
    const out = orderByIds(list, [14, '13', 12, '11']);
    assert.equal(out.length, 4);
    assert.ok(out.every((b) => list.includes(b)));
});

test('a drop back in place, or ids that are not exactly the entries, change nothing', () => {
    assert.equal(orderByIds(blocks(), ['11', '12', '13', '14']), null);
    assert.equal(orderByIds(blocks(), ['11', '12', '13']), null); // one missing
    assert.equal(orderByIds(blocks(), ['11', '12', '13', '99']), null); // a stranger (a drag clone, a stale id)
    assert.equal(orderByIds(blocks(), ['11', '11', '13', '14']), null); // twice
    assert.equal(orderByIds([...blocks().slice(0, 3), undefined], ['11', '12', '13', '']), null);
    assert.equal(orderByIds(null, []), null);
});

test('socials are keyed by their _uid', () => {
    const socials = [{ _uid: 'a', platform: 'x' }, { _uid: 'b', platform: 'instagram' }];
    assert.deepEqual(orderByIds(socials, ['b', 'a'], (s) => s._uid).map((s) => s.platform), ['instagram', 'x']);
});

// A list element as Sortable leaves it: the template, the items, perhaps the drag clone.
function fakeList(children) {
    return {
        children,
        insertBefore(node, ref) {
            const i = this.children.indexOf(node);
            if (i >= 0) this.children.splice(i, 1);
            const at = ref ? this.children.indexOf(ref) : this.children.length;
            this.children.splice(at, 0, node);
        },
    };
}
const node = (id, extra = []) => ({ dataset: { id }, classList: { contains: (c) => extra.includes(c) }, matches: (sel) => sel === '.block-item' });
const template = { dataset: {}, classList: { contains: () => false }, matches: () => false };

test('the ids are read from the items only, without the template or the drag clone', () => {
    const list = fakeList([template, node('11'), node('12'), node('12', ['sortable-fallback']), node('13')]);
    assert.deepEqual(itemIds(list), ['11', '12', '13']);
});

test('the dropped node goes back to its place among the items', () => {
    const a = node('11'), b = node('12'), c = node('13');
    const list = fakeList([template, b, c, a]); // a (first) was dropped last
    putBack(list, a, 0);
    assert.deepEqual(list.children.map((n) => n.dataset.id), [undefined, '11', '12', '13']);
    const last = fakeList([template, c, a, b]); // c (last) was dropped first
    putBack(last, c, 2);
    assert.deepEqual(last.children.map((n) => n.dataset.id), [undefined, '11', '12', '13']);
});

// ---- the end of a drop (finishDrop), with nodes shaped like the editor's list ----
const style = { dataset: {}, classList: { contains: () => false }, matches: () => false, tag: 'style' };
const itemNode = (id) => ({ ...node(id), parentNode: null, remove() { this.gone = true; } });
const listOf = (children) => { const l = fakeList(children); children.forEach((c) => { c.parentNode = l; }); return l; };

test('a drop is read by ids, not by the oldIndex and newIndex of Sortable, whatever else stands in the list', () => {
    // a <style> and the template stand before the items: index-based reading would be off by two
    const a = itemNode('11'), b = itemNode('12'), c = itemNode('13');
    const list = listOf([style, template, b, c, a]); // a (first) was dropped last
    let got;
    const out = finishDrop(list, { item: a, oldIndex: 2, newIndex: 4, oldDraggableIndex: 0, newDraggableIndex: 2 }, (ids) => { got = ids; });
    assert.equal(out.error, undefined);
    assert.deepEqual(got, ['12', '13', '11']); // the order as dropped
    assert.deepEqual(list.children.filter((n) => n.dataset.id).map((n) => n.dataset.id), ['11', '12', '13']); // node back in place
    assert.equal(list.children[0], style); // nothing else was moved
});

test('a throw while applying a drop never escapes and always lifts the Alpine ignore flag', () => {
    const a = itemNode('11');
    const list = listOf([template, a]);
    list._x_ignoreMutationObserver = true;
    const out = finishDrop(list, { item: a, oldDraggableIndex: 0 }, () => { throw new Error('boom'); });
    assert.equal(out.error.message, 'boom');
    assert.equal('_x_ignoreMutationObserver' in list, false);
});

test('a dropped node that Alpine already threw away is not put back into the list', () => {
    const live = itemNode('11'), dead = itemNode('12');
    dead.parentNode = null; // the keyed list replaced it while it was being dragged
    const list = listOf([template, live]);
    finishDrop(list, { item: dead, oldDraggableIndex: 0 }, () => {});
    assert.deepEqual(list.children.map((n) => n.dataset.id), [undefined, '11']);
});

test('sameOrder tells "nothing moved" from "the ids do not match the data"', () => {
    assert.equal(sameOrder(blocks(), ['11', '12', '13', '14']), true);
    assert.equal(sameOrder(blocks(), ['12', '11', '13', '14']), false);
    assert.equal(sameOrder(blocks(), ['11', '12', '13']), false);
});

test('after a refused drop the nodes the data does not know, or knows twice, leave the list', () => {
    const n11 = itemNode('11'), n12 = itemNode('12'), ghost = itemNode('99'), twin = itemNode('11');
    const list = listOf([template, n11, ghost, n12, twin]);
    const removed = dropStray(list, blocks().slice(0, 2));
    assert.equal(removed, 2);
    assert.ok(ghost.gone && twin.gone && !n11.gone && !n12.gone);
});

import { reorderRequest, reorderFailure } from '../../resources/js/drop-move.js';

test('the order save carries the page revision, and leaves out blocks that are not saved yet', () => {
    const list = [{ id: 11 }, { id: 1700000000000 }, { id: 12 }];
    assert.deepEqual(reorderRequest(list, '2026-11-14T01:02:03.000000Z'), { links: [{ id: 11, order: 0 }, { id: 12, order: 1 }], base_updated_at: '2026-11-14T01:02:03.000000Z' });
    assert.deepEqual(reorderRequest(list, null), { links: [{ id: 11, order: 0 }, { id: 12, order: 1 }] });
});

test('a refused order save says why: expired, changed in another tab, block gone, or plain failure', () => {
    assert.deepEqual([419, 409, 422, 500, 0].map(reorderFailure), ['expired', 'conflict', 'gone', 'saveFailed', 'saveFailed']);
});
