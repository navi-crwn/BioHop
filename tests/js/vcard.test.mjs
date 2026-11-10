// Run: node --test tests/js/vcard.test.mjs. Expected strings are written by hand from the RFCs.
import test from 'node:test';
import assert from 'node:assert/strict';
import { vcardEscape, foldLine, buildVcard, vcardFileName, splitName } from '../../resources/js/vcard.mjs';

// Hard values from the QR decode proof.
const hard = { firstName: 'Ann;Marie', lastName: 'Doe, Jr.', org: 'Kopi, Hop; Lt.2', mobile: '+62 812-0000-0000', email: 'ann.doe@example.com' };

const unesc = (s) => s.replace(/\\([\\;,nN])/g, (_, c) => (c === 'n' || c === 'N' ? '\n' : c));
function semi(s) {
    const p = [];
    let cur = '';
    for (let i = 0; i < s.length; i++) {
        if (s[i] === '\\') cur += s[i] + s[++i];
        else if (s[i] === ';') { p.push(cur); cur = ''; } else cur += s[i];
    }
    p.push(cur);
    return p;
}
// vCard 3.0: unfold, split on CRLF, split the name from the value at the first colon.
function parse(text) {
    return text.replace(/\r\n[ \t]/g, '').split('\r\n').filter((l) => l !== '').map((l) => {
        const k = l.indexOf(':');
        return { name: l.slice(0, k).split(';')[0], value: l.slice(k + 1) };
    });
}

test('escape: backslash, semicolon, comma, line breaks', () => {
    assert.equal(vcardEscape('Doe, John; Jr\\'), 'Doe\\, John\\; Jr\\\\');
    assert.equal(vcardEscape('a\nb\r\nc\rd'), 'a\\nb\\nc\\nd');
    assert.equal(vcardEscape('a\u0000b'), 'ab');
});

test('hard values round-trip through a parser, N and FN present, CRLF only', () => {
    const t = buildVcard(hard);
    assert.ok(!/[^\r]\n/.test(t) && !/\r[^\n]/.test(t));
    const p = parse(t);
    assert.deepEqual(p.map((x) => x.name), ['BEGIN', 'VERSION', 'N', 'FN', 'ORG', 'TEL', 'EMAIL', 'END']);
    assert.deepEqual(semi(p[2].value).map(unesc), ['Doe, Jr.', 'Ann;Marie', '', '', '']);
    assert.equal(unesc(p[3].value), 'Ann;Marie Doe, Jr.');
    assert.equal(unesc(p[4].value), 'Kopi, Hop; Lt.2');
});

test('a line break in a value cannot inject a property', () => {
    const evil = 'x\r\nNOTE:injected\nEND:VCARD\nBEGIN:VCARD';
    const t = buildVcard({ firstName: evil, lastName: evil, org: evil, mobile: evil, email: 'a@b.co' }, { fold: true });
    const names = parse(t).map((x) => x.name);
    assert.equal(names.filter((n) => n === 'NOTE').length, 0);
    assert.equal(names.filter((n) => n === 'BEGIN').length, 1);
    assert.equal(names.filter((n) => n === 'END').length, 1);
});

test('no name and no company gives nothing; company alone becomes FN', () => {
    assert.equal(buildVcard({}), '');
    assert.ok(buildVcard({ org: 'Only Org' }).includes('FN:Only Org'));
});

test('an invalid email is left out', () => {
    assert.ok(!buildVcard({ firstName: 'a', email: 'nope' }).includes('EMAIL'));
});

test('folding: 75 octets, continuation starts with a space, unfolds to the original', () => {
    const o = { firstName: 'a', org: '日本語'.repeat(40) };
    const flat = buildVcard(o);
    const folded = buildVcard(o, { fold: true });
    for (const l of folded.split('\r\n')) assert.ok(Buffer.byteLength(l) <= 75, l);
    assert.equal(folded.replace(/\r\n /g, ''), flat);
    assert.deepEqual(foldLine('abc', 75), ['abc']);
});

test('final adds a trailing CRLF', () => {
    assert.ok(buildVcard({ firstName: 'a' }, { final: true }).endsWith('END:VCARD\r\n'));
    assert.ok(buildVcard({ firstName: 'a' }).endsWith('END:VCARD'));
});

test('the file name is safe', () => {
    assert.equal(vcardFileName('Dewi Lestari'), 'Dewi_Lestari.vcf');
    assert.equal(vcardFileName('../../etc/passwd'), 'etcpasswd.vcf');
    assert.equal(vcardFileName('a"b<c>d|e:f*g?h'), 'abcdefgh.vcf');
    assert.equal(vcardFileName('a\r\nb\u0000c'), 'a_bc.vcf');
    assert.equal(vcardFileName(''), 'contact.vcf');
    assert.equal(vcardFileName('...'), 'contact.vcf');
    assert.equal(Array.from(vcardFileName('x'.repeat(200))).length, 84);
});

test('splitName: the last word is the family name', () => {
    assert.deepEqual(splitName('Dewi Ayu Lestari'), { firstName: 'Dewi Ayu', lastName: 'Lestari' });
    assert.deepEqual(splitName('HEL.ink'), { firstName: 'HEL.ink', lastName: '' });
    assert.deepEqual(splitName(null), { firstName: '', lastName: '' });
});
