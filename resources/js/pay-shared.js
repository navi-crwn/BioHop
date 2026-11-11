// Shared by the editor, the public page and the tests: turns a stored payment block into hop-pay parameters and
// checks them with hop-pay itself (no parallel validation). Pure functions, no DOM.
//
// A stored block holds settings.pay = { format, fields: {key: value}, amount, note, open_amount }.
// `formats` is resources/pay/formats.json (made from the module's own format list by scripts/vendor-hop-pay.mjs).
import { build, HopPayError } from './vendor/hop-pay.js';

const blank = (v) => v === undefined || v === null || String(v).trim() === '';

/** The parameters hop-pay builds from: the identity fields, the amount, and the note under the format's own key. */
export function payParams(pay, def, amount) {
    const p = {};
    for (const [k, v] of Object.entries(pay.fields || {})) if (!blank(v)) p[k] = String(v).trim();
    const a = amount === undefined ? pay.amount : amount;
    if (!blank(a)) p.amount = String(a).trim();
    if (def.note && !blank(pay.note)) p[def.note] = String(pay.note).trim();
    return p;
}

/**
 * Checks a block with the module. Returns { ok: true, payload, display, warnings } or { ok: false, code, field }.
 * `amount` overrides the stored one (a visitor typing an amount on an open block). An empty required field is
 * reported as code "required" before the module is asked, so the message says what is missing.
 */
export function payCheck(pay, formats, amount) {
    const def = pay && formats[pay.format];
    if (!def) return { ok: false, code: 'unknown_format', field: 'format' };
    for (const f of def.fields) {
        if (!f.optional && f.kind !== 'currency' && blank((pay.fields || {})[f.key])) return { ok: false, code: 'required', field: f.key };
    }
    try {
        const r = build(pay.format, payParams(pay, def, amount));
        return { ok: true, payload: r.payload, display: r.display, warnings: r.warnings };
    } catch (e) {
        if (e instanceof HopPayError) return { ok: false, code: e.code, field: e.field };
        throw e;
    }
}

/** A starting block for a format: empty fields, the format's default currency chosen where it has one. */
export function emptyPay(formats, id) {
    const first = id || Object.keys(formats)[0];
    const fields = {};
    for (const f of formats[first].fields) if (f.kind === 'currency') fields[f.key] = f.default || formats[first].currencies[0];
    return { format: first, fields, amount: '', note: '', open_amount: false };
}
