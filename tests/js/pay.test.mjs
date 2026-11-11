// Run: node --test tests/js/pay.test.mjs   (Pest runs it too: tests/Feature/PayBlockTest.php)
// The vectors below are the fixed ones of PayHop's own test file (web/src/lib/pay/hop-pay.test.mjs): the vendored module
// has to give exactly these payloads. PromptPay and VietQR have no official vector in the module either, so they are
// checked for structure and the CRC with the module's own parser.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import * as H from '../../resources/js/vendor/hop-pay.js';
import { payCheck, payParams, emptyPay } from '../../resources/js/pay-shared.js';
import { buildFormats } from '../../scripts/vendor-hop-pay.mjs';

const formats = JSON.parse(readFileSync(new URL('../../resources/pay/formats.json', import.meta.url), 'utf8'));
const SYS = '11111111111111111111111111111111';
const IBAN = 'DE89 3704 0044 0532 0130 00';

// One valid stored block per format, with the payload the module must give.
const VECTORS = {
    paypal: [{ format: 'paypal', fields: { user: 'jdoe', currency: 'USD' }, amount: '12.5' }, 'https://paypal.me/jdoe/12.50USD'],
    cashapp: [{ format: 'cashapp', fields: { cashtag: '$jdoe' }, amount: '5' }, 'https://cash.app/$jdoe/5.00'],
    venmo: [{ format: 'venmo', fields: { user: '@jane-doe' }, amount: '3.5', note: 'Lunch & drinks' }, 'https://venmo.com/jane-doe?txn=pay&amount=3.50&note=Lunch%20%26%20drinks'],
    solana: [{ format: 'solana', fields: { recipient: SYS, label: 'Cafe Ana' }, amount: '1.5', note: 'Order #12' }, 'solana:' + SYS + '?amount=1.5&label=Cafe%20Ana&message=Order%20%2312'],
    upi: [{ format: 'upi', fields: { name: 'Warung Sri', vpa: 'shop@okbank' }, amount: '250', note: 'Order 7' }, 'upi://pay?pa=shop@okbank&pn=Warung%20Sri&am=250.00&cu=INR&tn=Order%207'],
    epc: [{ format: 'epc', fields: { name: 'Taman Baca Fund', iban: IBAN }, amount: '12.34', note: 'Donation' }, ['BCD', '002', '1', 'SCT', '', 'Taman Baca Fund', 'DE89370400440532013000', 'EUR12.34', '', '', 'Donation'].join('\n')],
    promptpay: [{ format: 'promptpay', fields: { name: 'Nama Contoh', phone: '060 000 0000' }, amount: '250' }, null],
    // 970436 is a real, publicly listed bank identifier, kept so the sample looks like a real transfer; the account number is invented
    vietqr: [{ format: 'vietqr', fields: { name: 'Nguyen Van A', bin: '970436', account: '0123456789' }, amount: '50000' }, null],
};

test('the form list is the module\'s own format list, and every format has a form', async () => {
    assert.deepEqual(Object.keys(formats).sort(), Object.keys(H.formats).sort());
    assert.deepEqual(Object.keys(VECTORS).sort(), Object.keys(H.formats).sort(), 'a new module format needs a vector here');
    // resources/pay/formats.json is exactly what scripts/vendor-hop-pay.mjs builds from the vendored module + fields.json
    assert.equal(JSON.stringify(formats, null, 4) + '\n', await buildFormats());
    for (const [id, d] of Object.entries(formats)) {
        assert.ok(d.fields.some((f) => f.key === d.payee), id + ' payee field');
        assert.ok(d.currencies.length, id + ' currencies');
    }
});

test('the vendored file says where it came from', () => {
    const head = readFileSync(new URL('../../resources/js/vendor/hop-pay.js', import.meta.url), 'utf8').slice(0, 900);
    assert.match(head, /navi-crwn\/PayHop/);
    assert.match(head, /commit [0-9a-f]{40}/);
    assert.match(head, /MIT/);
});

for (const [id, [pay, want]] of Object.entries(VECTORS)) {
    test(id + ': a stored block gives the module\'s payload', () => {
        const r = payCheck(pay, formats);
        assert.equal(r.ok, true, JSON.stringify(r));
        if (want !== null) assert.equal(r.payload, want);
        else {
            assert.equal(H.checkCrc(r.payload), true, 'EMV CRC');
            const t = H.parseEmv(r.payload);
            assert.ok(t['54'], 'a fixed amount makes tag 54');
        }
        assert.equal(r.display.tested, false);
        assert.ok(r.display.receiverName);
    });
}

test('invalid blocks are refused by the module, with its own error code and field', () => {
    const bad = (pay, code, field) => {
        const r = payCheck(pay, formats);
        assert.equal(r.ok, false);
        assert.equal(r.code, code);
        assert.equal(r.field, field);
    };
    bad({ format: 'paypal', fields: { user: 'j doe', currency: 'USD' } }, 'bad_handle', 'user');
    bad({ format: 'paypal', fields: { user: 'jdoe', currency: 'USD' }, amount: '1,000.50' }, 'amount_separator', 'amount');
    bad({ format: 'cashapp', fields: { cashtag: '12345' } }, 'bad_handle', 'cashtag');
    bad({ format: 'venmo', fields: { user: 'ab' } }, 'bad_handle', 'user');
    bad({ format: 'solana', fields: { recipient: SYS + 'x', label: 'A' } }, 'bad_address', 'recipient');
    bad({ format: 'upi', fields: { name: 'A', vpa: 'shop' } }, 'bad_handle', 'vpa');
    bad({ format: 'epc', fields: { name: 'A', iban: 'DE89 3704 0044 0532 0130 01' } }, 'bad_iban', 'iban');
    bad({ format: 'epc', fields: { name: 'A'.repeat(71), iban: IBAN } }, 'too_long', 'name');
    bad({ format: 'promptpay', fields: { name: 'A', phone: '12345' } }, 'bad_handle', 'phone');
    bad({ format: 'promptpay', fields: { name: 'A', phone: '0600000000' }, amount: '0' }, 'amount_not_positive', 'amount');
    bad({ format: 'vietqr', fields: { name: 'A', bin: '970436', account: '123' }, amount: '50.5' }, 'amount_no_decimals', 'amount');
    bad({ format: 'nope', fields: {} }, 'unknown_format', 'format');
    // an empty required field is named before the module is asked
    bad({ format: 'upi', fields: { name: '', vpa: 'a@b' } }, 'required', 'name');
});

test('hidden characters in a payee name are removed by the module, not carried into the payload', () => {
    const r = payCheck({ format: 'upi', fields: { name: 'Safe‮ynoM​ Shop', vpa: 'a1@bank' } }, formats);
    assert.equal(r.ok, true);
    assert.equal(r.display.receiverName, 'Safe ynoM Shop');
    assert.ok(!r.payload.includes('%E2%80%AE'));
});

test('an open amount: the visitor\'s amount is checked and replaces the stored one', () => {
    const pay = { format: 'paypal', fields: { user: 'jdoe', currency: 'EUR' }, amount: '', note: '', open_amount: true };
    assert.equal(payCheck(pay, formats).payload, 'https://paypal.me/jdoe');
    assert.equal(payCheck(pay, formats, '7.5').payload, 'https://paypal.me/jdoe/7.50EUR');
    assert.equal(payCheck(pay, formats, '7,5,0').code, 'amount_separator');
    assert.equal(payCheck(pay, formats, '-3').code, 'amount_format');
    // a fixed amount is not overridden when no visitor amount is given
    assert.equal(payCheck({ ...pay, amount: '2' }, formats).payload, 'https://paypal.me/jdoe/2.00EUR');
});

test('the note goes under the format\'s own key, and formats without one ignore it', () => {
    assert.deepEqual(payParams({ format: 'upi', fields: { name: 'A', vpa: 'a@b' }, note: 'hi' }, formats.upi), { name: 'A', vpa: 'a@b', note: 'hi' });
    assert.deepEqual(payParams({ format: 'epc', fields: { name: 'A', iban: 'x' }, note: 'ref' }, formats.epc), { name: 'A', iban: 'x', reference: 'ref' });
    assert.equal(payParams({ format: 'paypal', fields: { user: 'a' }, note: 'hi' }, formats.paypal).note, undefined);
});

test('emptyPay picks the default currency of the format', () => {
    assert.equal(emptyPay(formats, 'paypal').fields.currency, 'USD');
    assert.equal(emptyPay(formats).format, Object.keys(formats)[0]);
});
