# Changelog

All notable changes to BioHop are listed here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). The `/changelog` page of the app shows the merged pull requests and commits; it is generated from the git history by `scripts/build-changelog.py` into `resources/changelog.json`.

## [Unreleased]

### Fixed
- Encrypted NoteHop keys are refused in everything a page or block saves, not only in address fields: the floating button (also when only its address or only its type is saved), HTML and text blocks (anchors and entities included), social links on every platform, block settings, FAQ, map, countdown, contact card, footer note, warning reason, status, custom CSS and QR settings. The bio and other free text also refuse a key written with an entity, a tab or a label in front. A refused form no longer flashes the key into the session. A note link without a key and ordinary HTML are accepted as before.
- A floating button already set to custom now gets the full address check when only its address is saved, and switching to custom checks the address already stored.
- `bio:scrub-note-keys` covers every column the rule guards and the forms above, leaves rows without a key untouched (a JSON row was rewritten and counted before), and counts owner tokens in sessions. Run it again with `--apply` after this update.
- The Linktree import stores a page's title, bio and block titles without a key, and leaves out a keyed social link or block.
- Avatar and image uploads answer 422 instead of 500 to a picture that is too large. The QR logo, uploaded or sent inside the settings, has the same pixel limit as other pictures.
- A draft no longer links to its public address or QR code, which answer 404: the page list shows the address as text, and the analytics and editor links appear once the page is published.
- Public bio pages, the password page and the editor preview name the shown language in `<html lang>`.
- `composer.json` pins `hop/ui` to the commit in the lock (`6b4210c`, same tree as before); the old pin was a commit outside its main history.
- The brand link in the public header and the New bio button have an accessible name when their text is hidden on phones.

## [1.0.0] - 2026-11-04

The first stable release. BioHop has been public since it was split out of LinkHop; 1.0.0 is the point where the feature set below is treated as the baseline that later releases build on. Everything under this heading is the full history to date.

- Link-in-bio pages from many block kinds (links, shop products, sign-up forms, polls, feeds, embeds, media), with themes, a setup wizard, versions you can restore, and analytics by page, link, source and country.
- No accounts of its own: people sign in with HOP ID at the hub. The hub tells BioHop about revoked sessions and deleted accounts (`POST /hop/events`), and BioHop acts on them even if it was down when the person signed out.
- Privacy by default: no CDN, font or icon host, visitor addresses kept only as a keyed hash for 30 days, third-party trackers loaded only after consent, and signed-in pages sent with `Cache-Control: no-store`.
- The interface, the READMEs and the documentation site are in nine languages: English, 简体中文, Deutsch, Bahasa Indonesia, 日本語, Português (Brasil), Español, Français and Русский.
- Self-hosting with `install.sh`, `update.sh` and `uninstall.sh`; the admin pages live in Imperium, the HOP control panel.
- The 2026-11-04 audit fixes are included, among them the refusal of links to encrypted NoteHop notes and the redirect of a suspended member to the hub's notice.

### Signing out at the hub now ends the session here too (even when BioHop was down)
- Signing in through HOP ID uses `remember: true`, so the remember cookie (30 days) brought a person straight back in after their session rows were deleted, and the hub's "end sessions" action and its chained logout could not reach an app that was down at that moment. The hub now queues a signed `session.revoked` to every app; `POST /hop/events` (and `/api/hop/events`) handle it: the session rows of that account are deleted (database and file drivers) and its `remember_token` is rotated, so the cookie stops working. The hub retries for about 16 hours while BioHop is unreachable.
- `account.deleted` also ends the account's sessions, after the page and upload purge that was already there. An event the hub sends twice (a retry) is handled once; a type BioHop does not know gets a 200 so the hub stops retrying it. The 403 for other apps and the 401 for unsigned requests are unchanged.
- A suspension needs nothing new: the next request of a suspended account is signed out and sent to the hub's `/suspended` notice, as before.
- Needs `hop/auth` with the event receiver (`^0.1.3`, the tag that holds it) and an `apps` row for `biohop` with the `events` capability at the hub.

### Added

Pages and blocks
- New page wizard. "New bio page" is six short steps with a live phone: address, what the page is for, the platforms you are on (with your handles), your first links (name and icon filled in from the address), profile (picture, name, bio, content warning) and colours (wallpaper and buttons). One request builds the finished page and opens the editor. The plain form stays at `?classic=1`.
- Import from Linktree: a public linktr.ee page becomes a draft page with its picture, links, groups, headers and social icons.
- Shop. A Product block has a picture, name, price with currency, a short description, a was-price, variants with their own price, stock and address, a stock count and a sold-out switch. It can fill its details in from the shop's address (2.5 second limit, own copy of the picture, soft failure). Once a page has a product, the public page shows a Links and Shop tab bar with a two-column grid, and the shop has its own address, `hel.ink/b/shop/{slug}`. A product opens its address in a new tab and counts as a click. `shop` and `click` are reserved slugs.
- Collections. A collection block groups everything under it, until the next collection or a divider, stacked or as a two-column grid.
- Archive and select mode. An archived block leaves the page and the list but keeps its clicks, and can be restored or deleted. In the editor, Select ticks several blocks to show, hide, archive or delete them together.
- More block kinds: status chip, floating button, app store redirect, goal bar, audio clip, picture carousel, PDF, WhatsApp rotator (up to 10 numbers, in turn or by weight), sign-up form, poll and RSS or Atom feed.
- Publish and expiry times on every block, optional UTM parameters on outgoing links, and routing rules on links that send visitors elsewhere by country, device, system and hour of the day.
- Sign-up form block with a consent box, entries stored for the page owner, CSV download, delete and a retention period in days (1 to 365, 90 by default).
- Webhook for form entries: signed JSON to an address the owner chooses, retried by the scheduler (`bio:webhooks:retry`), with a delivery log, a test button and resend.
- Poll block: one vote per device, results after voting, counts and reset for the owner.
- Feed block: the latest posts of an RSS or Atom feed, fetched through a guarded, cached reader.
- Page settings: share picture, favicon, Google Tag Manager, Unlisted and privacy mode switches, an address to send visitors to while the page is unpublished, a footer line and a PIN per link.
- Header layouts (classic, hero, banner with a cover picture, cutout) and a Design tab with Header, Theme, Wallpaper, Text, Buttons and Footer sections, button colours and a "Powered by" switch.
- A three-level content warning (none, sensitive, adult 18+) with a 24-hour cookie and a translated screen, and bio pages are classified before publication.

Editor and dashboard
- Versions: an automatic version before each editing session, named versions, and one-click restore that can itself be undone.
- Bento block picker, command palette (`Ctrl/Cmd+K`), page switcher, sort by clicks and bulk move to a collection.
- Dashboard home: a greeting, clicks this week against last week, a setup checklist and 7-day lines per page.
- Analytics by source (QR code, NFC tag, installed web app) and by referring site, an installable web app manifest per page, and a story picture from the share dialog.
- Dashboard chrome: the top bar holds settings-type controls only (primary action, language, theme and an account menu with your HOP ID, settings and sign out); the other HOP apps live in the sidebar with their marks.

Home page
- The home page is a long scroll tour. The hero shows the real hel.ink/b/hel page live in a phone (an iframe of `?embed=1`) while pixels assemble into the BioHop logo. Every feature has a scene: blocks that fly into the phone, a pinned story from claiming a name to reading the numbers, a ring of themes that dresses the phone, an isometric chart of clicks per day, a share map, the password and 18+ gates, CSS that types itself, and a self-host terminal. The HOP family appears as 3D marks. GSAP and Lenis are bundled with the app. With "reduce motion" on, or without WebGL, every scene shows its finished state.
- `php artisan bio:official-page --apply` fills the official HEL.ink page from the shared manifest: a "Hop in" list of the public HOP apps, one of every kind of block, "Start here" links and a FAQ. Blocks added by hand stay when you run it again. The page's video and music come from `HOP_OFFICIAL_VIDEO` and `HOP_OFFICIAL_MUSIC`.
- Only the official page (`HOP_OFFICIAL_BIO`, default `hel`), and only as `?embed=1`, may be framed by the HOP sites. An embedded view does not count as a visit, its links open in a new tab and it stays out of search. Every other page keeps `frame-ancestors 'none'`.
- Dedicated pages on bio.hel.ink (features, about, help, FAQ, contact, terms and privacy), a theme gallery and a BioHop abuse report page.

Languages and documentation
- Nine languages: English, 简体中文, Deutsch, Bahasa Indonesia, 日本語, Português (Brasil), Español, Français and Русский. German and Russian are new, every catalog is translated, and Arabic and right-to-left layout are removed. An older stored `zh` or `pt` means `zh-CN` or `pt-BR`, and a retired language falls back to English.
- READMEs and the documentation site in the nine languages.
- `install.sh`, `update.sh` and `uninstall.sh`: a one-command installer that checks the computer, asks a few questions and can install the HOP ID hub next to BioHop; an updater that copies the database first; an uninstaller that keeps your data unless you pass `--purge`.
- `CREDITS.md` for the third-party software, fonts, icons and services BioHop ships or calls, `SECURITY.md`, issue templates and a CI check that the README carries the canonical HOP family table.

Integration with the HOP family
- Signed per-account summary endpoint for the hub dashboard.
- Admin API for Imperium (`/api/hop/*`), cleanup of a member's bio files when the account is deleted, KPI fields in `/api/hop/stats`, a periodic queue heartbeat, and `/hop/events` accepts events from the hub only.
- Two SSO secrets at once (`HOP_SSO_SECRET_PREV`) for rotation without downtime.
- Shared database with the hub and LinkHop: migrations are logged in `migrations_bio`, a baseline migration records the bio migrations LinkHop already ran, and every bio migration checks before it creates or adds anything.

Core (first release of the split)
- BioHop, the link-in-bio app of the HOP family, split out of LinkHop (plan: `docs/planning/PLAN-pemisahan-aplikasi.md`, Phase D).
- The `hop/bio` package (now owned by this repository): public pages at `hel.ink/b/{slug}`, `/b/{slug}/qr` and `/b/click/{id}`, the editor at `bio.hel.ink/bio`, themes, brands, uploads and analytics.
- Sign-in through HOP ID (`hop/auth`): no local login or registration. `/login` sends people to the hub and `/logout` ends the session here, then at the hub.
- Short links for blocks through LinkHop's API (`App\Bio\LinkHopClient`), with a per-member service token (scope `link:create`) created on first use and kept in `bio_linkhop_tokens`. When LinkHop cannot be reached the block keeps its original URL and the editor says so.
- URL and content safety through `hop/safety` (shared rules, shared domain blacklist), `abuse:scan` for bio pages and blocks, `privacy:prune` (bio click logs, 30 days), a sitemap of indexable pages and a content security policy with the bio embed hosts.
- GitHub Actions: tests (Pest, sqlite), Pint and `composer audit`.

### Changed

- No CDN, font or icon host. The content security policy no longer names jsdelivr, unpkg, Google Fonts, Bunny Fonts or flagcdn. Countries without a flag file show their code instead of a picture from flagcdn.com, the unused Simple Icons helper and the editor's Simple Icons requests are gone (the local brand icons are used), and the error page no longer loads the Tailwind CDN. Fonts for the public page and the editor preview are served from the app. The only other hosts the policy names are those of a page owner's own optional pixels (Google, Meta and TikTok), which load only after the visitor agrees, and Cloudflare for the abuse form's bot check.
- Editing saves by itself: about a second after you stop, only what changed is sent (one field, one block, the new order). A status next to the button says "All changes saved", "Saving…" or "Not saved", `Ctrl/Cmd+S` saves at once, and autosave can be switched off. A problem is shown where you are working, with the field's own message, a jump to the right tab and a Try again button.
- The editor is one page that scrolls normally, with the preview sticking beside it, instead of a fixed-height box. On phones the preview is a full-screen sheet opened from a Preview button.
- The editor's data comes in as JSON and its code is a cached file instead of a 3,000-line inline script. The page is about half the size, with half as many elements.
- Charts, the QR code library and the preview's Alpine are bundled with the app and loaded when needed, not from jsDelivr and unpkg.
- The sidebar lists every public HOP app from the shared manifest with the real marks (NoteHop was missing), and a single "Account (HOP ID)" entry.
- Speed: public pages get their own small script and stylesheet instead of the whole app bundle, pictures are shrunk to WebP at upload (`bio:optimize-images` converts old ones), players and maps load on tap, off-screen cards are not painted, a lite mode serves weak phones and Save-Data, the parsed user agent is cached, and the editor shows a page speed pill.
- Privacy and speed: a first-time reader of a plain public page gets no session row and no cookies.
- Webhook deliveries are pruned after seven days by `privacy:prune`.
- The session cookie is sent as Secure when `APP_URL` is https.
- The admin settings API's four limits (shortest address, pages per account, blocks per page, custom CSS) are stored in a `bio_settings` table and enforced by the editor, with defaults unchanged (5, 3, 100, allowed). A value out of range or malformed is refused with 422.
- GeoIP settings are read from config. Before, the local database was always reported unavailable and the web API keys in `.env` were ignored.
- The feed reader and the webhook sender connect to the addresses that were checked, so a second DNS answer cannot swap in a private address between the check and the connection.
- The installer, updater and uninstaller are safer. `set_env` writes a temporary file and renames it. `.env` is created private (0600) before any secret is written. `update.sh` restores the previous code when a step fails before the migration, and after the migration it keeps the site in maintenance mode and prints the way back. It copies the database consistently (`sqlite3 .backup`, or `mysqldump` with the password in a temporary 0600 option file), and `--yes` stops unless a copy exists or `--no-backup` is given. PHP, artisan and Composer run under `PHP_BIN`. Production installs get `LOG_LEVEL=warning`, and `TRUSTED_PROXIES=127.0.0.1` behind a local proxy.
- `composer.lock` points at the rewritten history of `hop-ui`, `hop-auth` and `hop-safety`, and `hop/auth` 0.1.2 is required.
- The admin pages moved to Imperium (the HOP control panel); `/admin` sends admins there.
- Home engine files are versioned by content, and the sync script follows HOP assets v2.
- User-facing copy: the public page, password gate and 404 page say BioHop instead of LinkHop; the 500 page no longer claims the team was notified; the help text points to Analytics on the pages list; the "counted without storing visitor IP addresses" text is removed, and so is the "compelling click-through" promise in the SEO preview. All nine language files follow.
- Fewer blurred layers and a fixed background that is composited rather than repainted, so scrolling is smooth.

### Fixed

- Files on public pages. A bio page is served from `hel.ink/b/{slug}` while uploads, built files and fonts live on the app host (`bio.hel.ink`), so every picture, icon, audio clip, PDF, carousel slide, cover, QR logo, share picture, Open Graph image, page font and web app icon is now addressed in full on that host (`ASSET_URL`, default `APP_URL`). Stored paths stay as they are; the address is built when the page is shown. Forms post to the host that served the page, the share dialog names the public host and uses the bundled QR library, and the web app manifest is linked on the page's own host.
- The sign-out chain reaches every HOP subdomain (`form-action` in the content security policy), so logout no longer stalls.
- An uploaded background picture now saves. The save used to refuse the file's own path as "a link type that is not allowed", so the page fell back to its theme. A replaced background picture is deleted once nothing uses it.
- SVG icons, QR logos and thumbnails now upload. Laravel's `image` rule refuses SVG, so every SVG failed. An SVG is rebuilt from an allowlist (no script, handlers, external files or entities) before it is stored, and one that cannot be made safe is refused with the reason.
- A link to hel.ink or a HOP app is kept as typed and no longer wrapped in a short link. `php artisan bio:unwrap-family-links` lists, and with `--apply` restores, blocks that were already wrapped.
- A block's custom icon was never saved for an existing block. Edits are now detected by content.
- Saving the SEO section failed (and could fail any save) when an older, longer SEO title was stored, because the whole page was sent and validated every time. Only changed fields are sent now; the SEO title accepts 60 characters and the description 160.
- The preview in the editor scrolls on every browser and on touch.
- The hero phone is sized by the stage's height, so it no longer spills over the buttons and the running line on phones or without WebGL. Counters never show a negative number for a frame.
- The root addresses `/b` and `/b/` redirect to the home page.
- The dashboard cards' title no longer straddles the gradient strip.
- Import from Linktree fetches the page and the picture through the guarded fetch, and reads unexpected page data as empty instead of failing. The list is capped at 200.
- The editor's short-link picker uses the registered search route, and the automatic title lookup that called a missing route is gone.
- `sitemap.xml` no longer lists pages of suspended owners, `/b` and `/b/` redirect to the configured app address, the public not-found page no longer asks for a missing logo, and `/up` is a plain answer without a CDN script or font.
- The 404, 429 and 500 pages, and the public bio "not found / not available" page, are translated into the nine languages and follow `?lang=` or the language cookie without touching the database (they were English only).
- `robots.txt` is now answered by the app (the static file is removed): it keeps `/bio/`, `/settings`, `/dashboard`, `/admin`, `/auth/`, `/login`, `/logout`, `/internal/`, `/hop/` and `/api/` out of search and carries a `Sitemap:` line with this install's address. Public bio pages stay open.
- An array in `lang`, `slug`, `query`, `next` or `app` in the address no longer causes a server error.
- A suspended member is sent to the hub's notice (`/suspended`) like in LinkHop; JSON callers still get 403.

### Security

- Click logs keep a keyed hash of the visitor's IP address (HMAC-SHA256 with `APP_KEY`), never the address, and `privacy:prune` deletes them after 30 days.
- Trackers a page owner adds (Google Analytics, Tag Manager, Facebook pixel, TikTok pixel) load only after the visitor agrees.
- Session cookie is Secure on https, install scripts no longer leave a world-readable `.env`, and an interrupted `set_env` cannot cut `.env` short.
- Outgoing fetches (feeds, webhooks) are pinned to the addresses that passed the safety check.
- Third-party software and services are listed in `CREDITS.md`.
- Links to encrypted NoteHop notes (the key is in the `#` part) are refused in link blocks, fallback and footer addresses, social links, bio text and the short-link form, and the abuse report form cuts the key out. `php artisan bio:scrub-note-keys` finds (and with `--apply` removes) keys saved earlier. Needs hop/safety with `EncryptedNoteLink`.
- Pages shown to a signed-in person are sent with `Cache-Control: no-store, private`, so Back after sign-out cannot show them again.
- The visitor's address is no longer in any cache key (page views, click caps, password, form and PIN limits, bans, geolocation answers); the keys hold the same keyed hash as the click log.
- `GEOIP_WEB_PROVIDERS=false` stops visitor addresses from going to web geolocation services. ip-api.com, which answered over plain http, is no longer used.

### Removed

- Arabic and right-to-left layout.
- The internal admin pages, now in Imperium.
- CDN requests for scripts, fonts and icons.

[Unreleased]: https://github.com/navi-crwn/BioHop/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/navi-crwn/BioHop/releases/tag/v1.0.0
