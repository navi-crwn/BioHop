// The "receive payment" block on a public page. Loaded only when the page has one (see public.js).
// The server already printed the payee and every detail as text; this draws the QR code from the same data in
// the browser and, for a block whose amount is open, validates what the visitor types with hop-pay and redraws.
// Nothing is sent anywhere. No animation.
import { payCheck } from './pay-shared.js';
import formats from '../pay/formats.json';

const SIZE = 224;

async function drawInto(host, payload) {
    host.replaceChildren();
    if (!window.loadQr) return false;
    await window.loadQr();
    const qr = new window.QRCodeStyling({
        width: SIZE, height: SIZE, type: 'canvas', data: payload, margin: 8,
        qrOptions: { errorCorrectionLevel: 'M' },
        dotsOptions: { color: '#000000', type: 'square' },
        backgroundOptions: { color: '#ffffff' },
    });
    qr.append(host);
    return true;
}

function init(el) {
    let cfg;
    try { cfg = JSON.parse(el.dataset.pay); } catch (e) { return; }
    const msgs = cfg.msgs || {};
    const qrBox = el.querySelector('[data-pay-qr]');
    const host = el.querySelector('[data-pay-canvas]');
    const err = el.querySelector('[data-pay-error]');
    const form = el.querySelector('[data-pay-open]');
    const input = form && form.querySelector('input');
    const text = el.querySelector('[data-pay-amount-text]');
    let seq = 0;

    const fail = (code) => {
        qrBox.hidden = true;
        err.textContent = msgs[code] || msgs.other || '';
        err.hidden = false;
    };
    const render = async (amount) => {
        const mine = ++seq;
        const r = payCheck(cfg.pay, formats, amount);
        if (!r.ok) { fail(r.code); return; }
        err.hidden = true;
        try {
            if (!(await drawInto(host, r.payload)) || mine !== seq) return;
            qrBox.hidden = false;
            if (text) text.textContent = r.display.amount ? r.display.amount + ' ' + r.display.currency : '';
        } catch (e) {
            fail('other');
        }
    };

    if (form) {
        form.hidden = false;
        let t;
        input.addEventListener('input', () => {
            clearTimeout(t);
            t = setTimeout(() => {
                const v = input.value.trim();
                // an empty field draws the code without an amount: the payer enters it in their own app
                render(v === '' ? '' : v);
            }, 250);
        });
        form.addEventListener('submit', (e) => { e.preventDefault(); render(input.value.trim()); });
    }
    render(form ? '' : undefined);
}

document.querySelectorAll('[data-pay]').forEach(init);
