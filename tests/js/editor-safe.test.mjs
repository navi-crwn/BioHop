// Run: node --test tests/js/editor-safe.test.mjs   (Pest runs it too: tests/Feature/EditorReorderTest.php)
import test from 'node:test';
import assert from 'node:assert/strict';
import { addOnce, removeById, postToPreview } from '../../resources/js/editor-safe.js';

test('a block that answers twice (double click on Restore) is in the list once', () => {
    const list = [{ id: 1 }, { id: 2 }];
    assert.equal(addOnce(list, { id: 3, title: 'A' }), true);
    assert.equal(addOnce(list, { id: 3, title: 'A again' }), false);
    assert.equal(addOnce(list, { id: '3' }), false); // the same id as text
    assert.equal(addOnce(list, {}), false); // no id at all
    assert.deepEqual(list.map((b) => b.id), [1, 2, 3]);
});

test('archiving removes the block by its id, not by the index remembered before a drag', () => {
    const list = [{ id: 1 }, { id: 2 }, { id: 3 }];
    const index = list.findIndex((b) => b.id === 1); // remembered before the request: 0
    list.push(list.shift()); // a drag moved block 1 to the end while the request was on its way
    assert.deepEqual(list.map((b) => b.id), [2, 3, 1]);
    assert.equal(removeById(list, 1).id, 1);
    assert.deepEqual(list.map((b) => b.id), [2, 3]); // splice(index, 1) would have removed block 2
    assert.equal(index, 0);
    assert.equal(removeById(list, 99), null);
});

// postMessage copies its data with the structured clone algorithm and refuses proxies (what Alpine's reactive objects are).
const strictWindow = () => { const sent = []; return { sent, postMessage(message) { structuredClone(message); sent.push(message); } }; };

test('a proxy hidden inside a block does not stop the preview message: it is sent again as a plain copy', () => {
    const win = strictWindow();
    const block = { id: 1, settings: new Proxy({ layout: 'grid' }, {}) };
    assert.throws(() => structuredClone({ blocks: [block] }), { name: 'DataCloneError' }); // what the old code ran into
    postToPreview(win, { type: 'preview-update', data: { blocks: [block] } }, 'http://localhost');
    assert.equal(win.sent.length, 1);
    assert.deepEqual(win.sent[0].data.blocks[0].settings, { layout: 'grid' });
});

test('a message that can be copied goes as it is, and other errors are not swallowed', () => {
    const win = strictWindow();
    const message = { type: 'preview-update', data: { blocks: [{ id: 1 }] } };
    postToPreview(win, message, 'http://localhost');
    assert.equal(win.sent[0], message);
    assert.throws(() => postToPreview({ postMessage() { throw new TypeError('other'); } }, message, 'x'), { name: 'TypeError' });
});
