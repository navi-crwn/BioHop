// The script for public bio pages (hel.ink/b/{slug}): only what a visitor needs, not the whole app.
// Alpine with collapse (FAQ, tabs), the delegated data-* actions (share, copy), Turnstile for the sign-up form,
// and the lazy QR loader for the share dialog. No editor, charts, drag-and-drop, modals or axios.
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import installInlineActions from './inline-actions';
import './turnstile';
import './lazy-libs';

Alpine.plugin(collapse);
window.Alpine = Alpine;
installInlineActions();
// Two frames later, so inline scripts in the page have run before Alpine starts.
requestAnimationFrame(() => requestAnimationFrame(() => Alpine.start()));
