// The script for public bio pages (hel.ink/b/{slug}): only what a visitor needs, not the whole app.
// Alpine with collapse (FAQ, tabs), the delegated data-* actions (share, copy), Turnstile for the sign-up form,
// and the lazy QR loader for the share dialog. No editor, charts, drag-and-drop, modals or axios.
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import installInlineActions from './inline-actions';
import './turnstile';
import './lazy-libs';
import { downloadVCard } from './vcard.mjs';

// A receive-payment block draws its QR code in the browser (hop-pay); the module is fetched only on pages that have one.
if (document.querySelector('[data-pay]')) import('./pay-block.js');

// An app block whose address belongs to a known app opens the app on a phone (app-links.js); the link works without it.
if (document.querySelector('[data-app]')) import('./app-links.js');

// Called by the vCard block's data-call button.
window.downloadVCard = downloadVCard;

Alpine.plugin(collapse);
window.Alpine = Alpine;
installInlineActions();
// Two frames later, so inline scripts in the page have run before Alpine starts.
requestAnimationFrame(() => requestAnimationFrame(() => Alpine.start()));
