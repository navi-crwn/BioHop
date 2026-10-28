import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import Sortable from 'sortablejs';
import modal from './modal';
import installInlineActions from './inline-actions';

Alpine.plugin(collapse);
Alpine.plugin(modal);
window.Alpine = Alpine;
window.Sortable = Sortable;
// data-call="fn" buttons call window.fn without inline on* handlers (CSP).
installInlineActions();
// Two frames later, so inline scripts in blade templates have run before Alpine starts.
requestAnimationFrame(() => requestAnimationFrame(() => Alpine.start()));

// CSRF for every axios request from the editor.
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token && window.axios) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}
