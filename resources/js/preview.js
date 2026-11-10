// The editor's live preview frame: Alpine, bundled with the app instead of loaded from a CDN.
import Alpine from 'alpinejs';
import { downloadVCard } from './vcard.mjs';

window.Alpine = Alpine;
window.downloadVCard = downloadVCard;
Alpine.start();
