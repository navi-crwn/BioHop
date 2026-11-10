// vCard 3.0 for the "Save contact" block. Pure functions (no DOM until downloadVCard), so Node tests can import them.
// RFC 2426 section 4 / RFC 6350 section 3.4: text values escape \ ; , and line breaks; lines end in CRLF;
// the .vcf file folds lines at 75 octets.

const CTRL = /[\u0000-\u0008\u000B\u000C\u000E-\u001F\u007F]/g;
const NEWLINES = /\r\n|[\n\r\u0085\u2028\u2029]/g;
const str = (v) => (v == null ? '' : String(v));

/** A text value: no raw line break survives, so a value can never start a new vCard line. */
export function vcardEscape(s) {
    return str(s).replace(NEWLINES, '\n').replace(CTRL, '')
        .replace(/[\\;,]/g, '\\$&').replace(/\n/g, '\\n');
}

/** Fold one content line at 75 octets without splitting a UTF-8 sequence. */
export function foldLine(line, max = 75) {
    const out = [];
    let cur = '';
    let bytes = 0;
    for (const ch of line) {
        const c = ch.codePointAt(0);
        const b = c < 0x80 ? 1 : c < 0x800 ? 2 : c < 0x10000 ? 3 : 4;
        if (bytes + b > max) { out.push(cur); cur = ' '; bytes = 1; }
        cur += ch;
        bytes += b;
    }
    out.push(cur);
    return out;
}

const EMAIL_RE = /^[^\s@;,:]+@[^\s@;,:]+\.[^\s@;,:]+$/;

/** o: { firstName, lastName, org, mobile, email }. opts: { fold, final }. Returns '' when there is no name. */
export function buildVcard(o = {}, opts = {}) {
    const g = (k) => str(o[k]).trim();
    const first = g('firstName');
    const last = g('lastName');
    const org = g('org');
    const fn = [first, last].filter(Boolean).join(' ') || org;
    if (!fn) return '';
    let lines = ['BEGIN:VCARD', 'VERSION:3.0'];
    lines.push('N:' + [last, first, '', '', ''].map(vcardEscape).join(';'));
    lines.push('FN:' + vcardEscape(fn));
    if (org) lines.push('ORG:' + vcardEscape(org));
    if (g('mobile')) lines.push('TEL;TYPE=CELL:' + vcardEscape(g('mobile')));
    if (g('email') && EMAIL_RE.test(g('email'))) lines.push('EMAIL;TYPE=INTERNET:' + vcardEscape(g('email')));
    lines.push('END:VCARD');
    if (opts.fold) lines = lines.flatMap((l) => foldLine(l));
    return lines.join('\r\n') + (opts.final ? '\r\n' : '');
}

/** A safe file name for the .vcf download: no path characters, no controls, 80 characters at most. */
export function vcardFileName(name) {
    let n = str(name).replace(NEWLINES, ' ').replace(CTRL, '').replace(/[\\/:*?"<>|]+/g, '')
        .replace(/\s+/g, '_').replace(/^[._]+|[._]+$/g, '');
    n = Array.from(n).slice(0, 80).join('');
    return (n || 'contact') + '.vcf';
}

/** The block stores one name field: the last word is the family name. */
export function splitName(name) {
    const parts = str(name).trim().split(/\s+/).filter(Boolean);
    if (parts.length < 2) return { firstName: parts[0] || '', lastName: '' };
    return { lastName: parts.pop(), firstName: parts.join(' ') };
}

export function downloadVCard(name, phone, email, company) {
    const text = buildVcard({ ...splitName(name), org: company, mobile: phone, email }, { fold: true, final: true });
    if (!text) return;
    const url = URL.createObjectURL(new Blob([text], { type: 'text/vcard;charset=utf-8' }));
    const a = document.createElement('a');
    a.href = url;
    a.download = vcardFileName(name || company);
    a.click();
    URL.revokeObjectURL(url);
}
