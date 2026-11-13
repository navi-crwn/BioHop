// Small guards for editor state that the drag work depends on (B-1). Pure, so they are tested in tests/js.

// Puts an entry in a list once. Two answers for the same block (a double click on Restore) must not give two entries with
// one id: the keyed list and the reorder refuse a list that holds an id twice.
export function addOnce(list, entry, keyOf = (item) => item?.id) {
    const key = String(keyOf(entry) ?? '');
    if (key === '' || list.some((item) => String(keyOf(item) ?? '') === key)) return false;
    list.push(entry);
    return true;
}

// Removes the entry with this id, wherever it stands now (an index remembered before a request may point elsewhere after
// a drag). Returns the removed entry or null.
export function removeById(list, id, keyOf = (item) => item?.id) {
    const at = list.findIndex((item) => String(keyOf(item) ?? '') === String(id));
    return at < 0 ? null : list.splice(at, 1)[0];
}

// Sends a message to the preview frame. postMessage copies its data and refuses Alpine proxies (DataCloneError): when
// that happens the data is sent again as a plain copy, so a proxy hiding somewhere in a block never freezes the preview.
export function postToPreview(win, message, origin) {
    try {
        win.postMessage(message, origin);
    } catch (error) {
        if (error?.name !== 'DataCloneError') throw error;
        win.postMessage(JSON.parse(JSON.stringify(message)), origin);
    }
}
