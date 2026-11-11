# Changelog

All notable changes to BioHop are listed here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). The `/changelog` page of the app shows the merged pull requests and commits; it is generated from the git history by `scripts/build-changelog.py` into `resources/changelog.json`.

## [Unreleased]

### Added
- A "Receive payment" block: the page owner picks one of the payment formats of `hop-pay` (today PayPal.me, Cash App, Venmo, Solana Pay, UPI, EPC SEPA, PromptPay and VietQR), fills in its fields, and optionally sets a fixed amount, lets visitors type the amount, and adds a short note. The editor checks every field with the module itself and shows translated errors; the server checks the shape (format, field names, lengths, control characters) and sends the payee name and note through the same content checks as other block text. QRIS is not included.
- The public page prints the payee, the method, the account details, the amount and the note as text, so it reads without scripts; the QR code is drawn in the visitor's browser from the same data with `qr-code-styling` (already bundled), and an open amount is validated with the module and redrawn as it is typed. Nothing is sent to PayHop or any payment service, and nothing animates. A line under the block says that the visitor's own bank or wallet app shows who they are paying and that BioHop does not process payments.
- `resources/js/vendor/hop-pay.js`: a vendored copy of PayHop's payload module (MIT) with a provenance header, `resources/pay/formats.json` (the module's format list with the form of each format, built by the script) and `scripts/vendor-hop-pay.mjs` to refresh both from a PayHop checkout (`--check` verifies they agree). All new text is in nine languages.
- App blocks open the app on a phone: when the block's website address is an https address of a known app (YouTube, WhatsApp, Telegram, TikTok, ...; the list is the one of `hop/safety` `DeepLinks`) and the visitor is on an iPhone, iPad or Android phone, a tap opens the app through the library's own address and the click is still counted. Without scripts and on computers the button is the same tracked https link as before; only the https address is stored, and no address with another scheme is accepted from the page owner. The editor shows a one-line hint when the address will open in an app. `composer.json` follows `hop/safety` `dev-feat/deep-links` until the library is tagged.

### Changed
- The payment block's server check now also runs the cheap per-format checks (PayPal.me, Cash App, Venmo, UPI, BIC, PromptPay, VietQR patterns, the IBAN checksum, 32-byte Solana addresses), so a request that skips the editor cannot store details the module rejects; when the module rejects a stored block in the browser, the public page hides its detail rows and shows only the error.
- Updating a payment block with other settings and no `pay` key keeps the stored payment details.

## [1.2.0] - 2026-11-11

### Added
- PayHop (pay.hel.ink) joins the family: it is named in the FAQ, the home page FAQ and the official page text in nine languages, in the READMEs and on the Pages site, and `payhop` and `pay` are reserved bio slugs and usernames (`payhop-` also a reserved username prefix). `ReservedFamilyNamesTest` checks every app of the family manifest, not a fixed list.
- The silent account re-check of `hop/auth`: the home page, `/changelog`, the info pages and `/status` (and every signed-in page) confirm with the hub, at most every five minutes, that the account in this session is still the one signed in there. Switching accounts at the hub no longer leaves the old account here. `HOP_RECHECK=false` turns it off; public bio pages (`/b/{slug}`) never do it. `SilentRecheckTest` reproduces account A in the app and account B at the hub.
- `/changelog` reads `storage/app/changelog.json` when it exists; `update.sh` builds it from GitHub on each update (needs python3 and an authenticated `gh`, otherwise the committed `resources/changelog.json` stays in use). `scripts/build-changelog.py --out` writes it anywhere and lists PayHop among the public apps.
- `/changelog`, `/privacy`, `/terms` and every other page on the shared info layout carry `og:image` (the family preview, `public/images/brand/og.png`), `og:title`, `og:description`, `og:url` and `twitter:card`, so a shared link shows a preview.

### Changed
- hop-assets v9 (PayHop's tile, mark, 3D preset and name font): `public/hop-home/` and `public/hop-brand/` are refreshed from the hub, and `scripts/sync-hop-assets.sh` takes the app list from the manifest instead of a hand-written one. `hop/ui` `^0.4.0` and `hop/auth` `^0.2.0` are the tagged releases.
- The family table in the READMEs and `scripts/check-hop-family.sh` are the hub's v9 copies (the script takes `--ref` and `--from`).

### Fixed
- The contact block's "Save contact" file is built from escaped values: `\ ; ,` and line breaks in a name, number, e-mail or company can no longer add or end vCard properties (before, a line break in the name could inject a property). The file has the `N` and `FN` lines, CRLF line ends, lines folded at 75 octets, and a file name with no path characters. One module (`resources/js/vcard.mjs`) serves the public page and the editor preview; the button label is translated in nine languages; the server refuses line breaks and control characters in the block's name, phone and company.
- The editor's WhatsApp rotator form (message, numbers, labels, weights, sharing mode, block name and hint) is translated in nine languages.
- `filehop` is a reserved bio slug, like the other HOP apps.

### Changed
- `hop/auth` is pinned to `^0.1.5` and `hop/safety` to the release `^0.1.0` (it followed `dev-main`). `hop/ui` is unchanged.
- `scripts/sync-hop-assets.sh` copies the hop-assets version the HEL.ink manifest names, not a fixed `v3`. `scripts/build-changelog.py` lists FileHop among the public apps.

### Changed
- Home boxes are proportional: the engine, motion and bento files are the hop-assets v8 copies (byte-identical). The five family tiles sit in one row of five from 1100 px up (equal boxes of about 215 px), a 3 + 2 bento on tablets and two columns on phones with the last tile spanning both; the 3D art is capped at 160-200 px (130 px on phones) and the gate scenes at 140 px. The block grid on the BioHop home page keeps two boxes per row beside the phone.
- The BioHop mark is the new 8x8 pixel page with three lines (stepped corners), in the brand files, the favicon and `docs/assets/mark.svg`.

### Changed
- Boxes on the home page (and the features page) are laid out by the shared `hop-bento` grid from hop-assets v7 (byte-identical copies of `hop-bento.css`, `hop-motion.js`, `hop-hero3d.js`): whole rows for any box count, a calm reveal, a pointer spotlight and scenes that only run while a box is on screen. The family tiles settle in all five slots now that the v7 engine keeps unsettled scenes drawing.
- The "Part of the HOP family" section on the home page uses the hop-assets v6 3D engine (byte-identical copy, so the LinkHop and BioHop marks are the new ones) and the v6 WebP fallbacks for every app, FileHop included; the section number and eyebrow are gone. The tiles are five across on wide screens, a 3 + 2 bento on tablets and two columns with the last tile spanning both on phones, so no tile sits alone on the last row. The three content-warning gates on the LinkHop home page do the same at tablet width.

### Changed
- Every app shows the family row as the first row inside its footer, for everyone and in both looks: the HOP ID mark and "Part of Hop Easy Link" (linking to hel.ink). "Part of" is translated in all nine languages; the name stays in English. It replaces the strip that only the Clean style showed.
- The footer no longer has a language row; the language menu in the top bar is the one place to switch. The copyright line is right-aligned on desktop and centred on phones.
- Signing out sends the person to the hub with `return=<this app's home>`, so the hub's chained logout can bring them back here instead of to the hub's home.

### Fixed
- The `/changelog` page lists every merged pull request and tag again. The snapshot was never rebuilt for a release, and the build script read a stale `origin/main`; it now fetches first, and `scripts/build-changelog.py --check` (run on release branches in CI) fails when a merged PR or tag is missing.

## [1.1.0] - 2026-11-10

### FileHop joins the HOP family
- FileHop (file.hel.ink, file conversion in the browser) is named wherever BioHop lists its sibling apps: the FAQ, the home page and the official page's description, in all nine languages, and the HOP family table in every README. `filehop` and `filehop-` are reserved page names.
- The home page's HOP family row keeps an app's flat mark when the 3D engine has no block map for it, instead of drawing the NoteHop logo for it.
- HOP family assets move to v3 (FileHop's tile and 3D preset): the home page module in `public/hop-home/` is refreshed from the hub's v3 (keeping the flat-mark guard), `scripts/sync-hop-assets.sh` copies from v3, and the documentation site loads the family fonts from `hop-assets/v3` (the same file and hash as v1). The tests accept any asset version, since hop/ui takes it from the family manifest.

### Security
- The picker calls to PixelHop and NoteHop can be signed with a secret of their own, `HOP_PICKER_SECRET` (32+ characters), instead of this app's SSO secret. Until now PixelHop and NoteHop had to hold BioHop's SSO secret (`HOP_SSO_SECRET_BIOHOP`) to verify the picker, so a leak on either server could forge a hub ticket and sign in to BioHop as anyone. With the same `HOP_PICKER_SECRET` on all three apps, that copy can be removed. Empty, nothing changes. Needs navi-crwn/NoteHop and navi-crwn/PixelHop `fix/picker-secret` to verify it.

### Added
- "Choose from NoteHop" for a link block (`BIO_NOTEHOP_PICKER`, off by default; `BIO_NOTEHOP_URL`, `BIO_NOTEHOP_TIMEOUT`). The editor asks `GET /bio/media/notehop`, which calls NoteHop's `GET /api/v1/me/notes` server to server, signed like the PixelHop picker with `X-HOP-Actor` set to the member's HOP ID; the browser never talks to NoteHop. Only the member's public notes with a read-only link are offered, never an encrypted note. Each address is checked again here (NoteHop's origin, a bare note path, no query, no fragment, no note key) and is kept as typed, not shortened. A member who never signed in to NoteHop is told to do so once; when NoteHop is down the dialog says so and the address field keeps working. Search, "Load more", keyboard and screen-reader friendly, still with reduced motion; 8 new strings in the nine languages. Needs navi-crwn/NoteHop `feat/me-notes-api`.

### Fixed
- "Select existing link" in the block editor now lists the member's short links. It asked LinkHop's `GET /api/links`, which only knows a web session, so the list was always empty, and each search replaced the member's "BioHop" service token without deleting the old one, leaving one more token in their list per keystroke. It now asks `GET /api/me/links` (scope `link:read`, navi-crwn/LinkHop: needs that route deployed first), with the words typed and a limit of 20. A token LinkHop refuses (401, or 403 for a missing scope) is deleted from `api_tokens` before a new one is made, and only BioHop's own token; a suspended account (403 without a scope) does not get a new token per search.
- When LinkHop cannot be reached the picker says so ("LinkHop is not reachable right now. You can still paste any URL.") instead of "No links found", and the short-link message no longer shows the upstream error text (it could name an internal address). Both are translated.
- The link picker and the Link library dialog are translated into the nine languages (12 new strings), their buttons are real buttons for the keyboard, results are announced to screen readers, and the spinner stands still with reduced motion. A search sent with an array as `query` is a 422.

### Changed
- The dashboard follows the HOP control standard (`--hop-ctl-*` tokens): one radius and one height for inputs, selects and buttons, 44 px controls on touch screens and narrow windows alike.
- `composer.lock` moves `hop/safety` from `12d72dd` to `df5e938` (navi-crwn/hop-safety#9): the bundled domain blacklist (`fnjphdp.com` and its subdomains, the Telegram account-takeover kit of 2026-11-05), the kit's phrases as phishing keywords matched in the address too, and the `random_subdomain` signal (weight 30, never blocking on its own). No code change here; link blocks, the short-link form and `abuse:scan` pick the rules up at once.

### Added
- BioHop reads the shape of the Telegram account-takeover kit's address, with the same rules as LinkHop (navi-crwn/LinkHop#103): `app/Support/PhishingShape.php` scores a machine-made subdomain, a machine-made first path segment, the kit's own `/wqamhcsom` landing path and its one-letter `r=`/`t=` tracking pair. `App\Bio\SafetyAdapter` now refuses such a destination when a block or a social link is saved and names the signals on the flag (`phishing_shape`, `kit_path`, `random_path`, `tracking_params`) so `abuse:scan` disables an existing block; `abuse:scan` takes its scorer from that adapter, so a bio page cannot be used to get around a block LinkHop already applies to a short link. Ordinary product pages, and readable pages that merely carry two short tracking parameters, keep passing. Motivation: an account-takeover kit reached hel.ink on 2026-11-05 and its addresses are still live in the wild.

### Added
- Public bio pages can be served from Cloudflare's edge. A published page without a password, content warning, form or poll, opened by a visitor with no session, no sign-in and no chosen language, is sent with `Cache-Control: public, s-maxage=60, max-age=0, stale-while-revalidate=300` and `Vary: Cookie`, and sets no cookie; every other response stays `no-cache, private`. The visit is counted by a small beacon from the page (`POST /b/{slug}/view`, throttled like clicks, once a day per visitor by the click cookie or address and browser, crawlers ignored) instead of by the page request, so a page that came from the edge still counts; a page the origin serves itself counts as before. With `CLOUDFLARE_ZONE_ID` and `CLOUDFLARE_API_TOKEN` set, saving, publishing or unpublishing a page or changing one of its blocks queues one purge by URL of the page, its shop and its QR, NFC and installed-app entries on both hosts; a failed purge is logged and never delays the save. The README's "Behind Cloudflare" section has the Cache Rule to create. Motivation: measured from the origin a page answers in 36 to 175 ms, from Asia through the Singapore edge in 0.3 to 3.2 s.
- "Choose from PixelHop" in the editor (`BIO_PIXELHOP_PICKER`, off by default): a picture the member already uploaded to PixelHop can be picked for the image block, the carousel, the avatar, the cover and the favicon. The editor asks `GET /bio/media/pixelhop`, which calls PixelHop's `GET /api/v1/me/media` server to server, signed like every HOP App Protocol call with the SSO secret this app already has and `X-HOP-Actor` set to the member's HOP ID; the browser never talks to PixelHop. The file stays in PixelHop and the page keeps its public address, which is accepted only from the hosts in `BIO_PIXELHOP_HOSTS` (default `pixel.hel.ink,p.hel.ink`). Private, password-protected, expiring and content-warning pictures are never offered. When PixelHop is unreachable the dialog says so and the upload stays; a member without a PixelHop account is told to sign in there once. Needs PixelHop with `PIXELHOP_MEDIA_CLIENTS=biohop` (navi-crwn/PixelHop#35). No new storage or image processing here; NoteHop notes stay out.

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

[Unreleased]: https://github.com/navi-crwn/BioHop/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/navi-crwn/BioHop/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/navi-crwn/BioHop/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/navi-crwn/BioHop/releases/tag/v1.0.0
