// Run: node --test tests/js/drop-move.test.mjs   (Pest runs it too: tests/Feature/EditorReorderTest.php)
import test from 'node:test';
import assert from 'node:assert/strict';
import { orderByIds, itemIds, putBack } from '../../resources/js/drop-move.js';

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
