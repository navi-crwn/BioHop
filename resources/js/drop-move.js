// What a drop in a sortable list of the editor means for the array behind it (B-1, A-5).
// The new order is read from the ids of the items as they stand in the DOM after the drop. Sortable's
// oldIndex/newIndex count every child of the list, so anything else in the container (the blocks list held a <style>
// and its select bar) shifted them: the wrong block moved, or an empty entry entered the array and froze dragging
// until a reload. Only putting the dragged node back uses a position, and then the one among the items alone.

// list: the array behind the list; ids: data-id of the list's items, top to bottom; keyOf: an entry's id.
// Returns the reordered array, or null when nothing moved or the ids are not exactly the entries of the list (then
// the caller leaves its data alone and the next render puts the DOM back).
export function orderByIds(list, ids, keyOf = (entry) => entry?.id) {
    if (!Array.isArray(list) || !Array.isArray(ids) || ids.length !== list.length) return null;
    const byId = new Map();
    for (const entry of list) {
        const key = entry == null ? '' : String(keyOf(entry) ?? '');
        if (key === '' || byId.has(key)) return null;
        byId.set(key, entry);
    }
    const out = [];
    for (const id of ids) {
        const entry = byId.get(String(id));
        if (entry === undefined) return null;
        byId.delete(String(id));
        out.push(entry);
    }
    return out.every((entry, i) => entry === list[i]) ? null : out;
}

// The data-id of each draggable item of a list element, top to bottom; Sortable's drag clone is never counted.
export function itemIds(listEl, draggable = '.block-item') {
    return Array.from(listEl.children)
        .filter((el) => el.matches(draggable) && !el.classList.contains('sortable-fallback'))
        .map((el) => el.dataset.id);
}

// Puts the dropped node back where it was (among the items only), so the keyed x-for moves it from a DOM that matches
// its last render once the array changes.
export function putBack(listEl, item, oldDraggableIndex, draggable = '.block-item') {
    if (!Number.isInteger(oldDraggableIndex) || oldDraggableIndex < 0) return;
    const items = Array.from(listEl.children).filter((el) => el !== item && el.matches(draggable) && !el.classList.contains('sortable-fallback'));
    listEl.insertBefore(item, items[oldDraggableIndex] ?? null);
}

// True when the ids in the DOM are exactly the entries of the list, in the list's own order (nothing moved).
export function sameOrder(list, ids, keyOf = (entry) => entry?.id) {
    return Array.isArray(list) && Array.isArray(ids) && ids.length === list.length
        && list.every((entry, i) => String(keyOf(entry) ?? '') === String(ids[i]));
}

// After a refused drop: takes out the items the data does not know (a node Alpine already dropped) or knows twice, so the
// next render puts the list back in step with the data. Returns how many it took out.
export function dropStray(listEl, list, keyOf = (entry) => entry?.id, draggable = '.block-item') {
    const known = new Set((Array.isArray(list) ? list : []).map((entry) => String(keyOf(entry) ?? '')));
    const seen = new Set();
    let removed = 0;
    for (const el of Array.from(listEl.children)) {
        if (!el.matches(draggable) || el.classList.contains('sortable-fallback')) continue;
        const id = String(el.dataset.id);
        if (!known.has(id) || seen.has(id)) { el.remove(); removed++; } else seen.add(id);
    }
    return removed;
}

// What Sortable's end event does, kept apart from Alpine so it can be tested: read the ids of the items as they stand,
// put the dropped node back among the items (only if it is still in the list), lift the Alpine ignore flag, then let
// `onDrop(ids)` apply the order. Nothing escapes: a throw is returned as { error }. Returns { ids } on success.
export function finishDrop(listEl, evt, onDrop, draggable = '.block-item') {
    try {
        const ids = itemIds(listEl, draggable);
        if (evt?.item?.parentNode === listEl) putBack(listEl, evt.item, evt.oldDraggableIndex, draggable);
        delete listEl._x_ignoreMutationObserver;
        onDrop(ids);
        return { ids };
    } catch (error) {
        return { error };
    } finally {
        delete listEl._x_ignoreMutationObserver;
    }
}
