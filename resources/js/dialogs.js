// One door to the shared HOP dialogs (HopUI, hel.ink/hop-assets/v5/hop-ui.js). The layout loads that script only
// when the family manifest lists it, so until the hub serves v5, or when the script fails to load, the browser's
// own dialogs answer instead. Nothing here depends on the hub being up.
//
//   confirmDialog({ title, message, ok, danger, subject, anchor }) → Promise<boolean>
//   alertDialog({ title, message })                               → Promise<void>
//   promptDialog({ title, message, label, type })                 → Promise<string|null>
//
// For a delete, pass danger: true, subject: the name of the thing, anchor: the button that was pressed.

const hop = () => (window.HopUI && typeof window.HopUI.confirm === 'function' ? window.HopUI : null);
const plain = (o) => [o.title, o.subject && !String(o.title || '').includes(o.subject) ? '“' + o.subject + '”' : '', o.message].filter(Boolean).join('\n\n');

export function confirmDialog(o = {}) {
    const ui = hop();
    if (ui) return ui.confirm(o).then(Boolean);
    return Promise.resolve(window.confirm(plain(o)));
}

export function alertDialog(o = {}) {
    const ui = hop();
    if (ui) return ui.alert(o).then(() => undefined);
    window.alert(plain(o));
    return Promise.resolve();
}

export function promptDialog(o = {}) {
    const ui = hop();
    if (ui) return ui.prompt(o);
    const answer = window.prompt(plain(o) || o.label || '', o.value || '');
    return Promise.resolve(answer === null ? null : answer);
}

// Alpine expressions in the blade templates reach the same door through window.hopDialog.
window.hopDialog = { confirm: confirmDialog, alert: alertDialog, prompt: promptDialog };
