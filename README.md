<p align="center">🌐 <b>English</b> · <a href="README.zh-CN.md">简体中文</a> · <a href="README.de.md">Deutsch</a> · <a href="README.id.md">Bahasa Indonesia</a> · <a href="README.ja.md">日本語</a> · <a href="README.pt-BR.md">Português (Brasil)</a> · <a href="README.es.md">Español</a> · <a href="README.fr.md">Français</a> · <a href="README.ru.md">Русский</a></p>

# BioHop

<p align="center">
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="MIT license"></a>
  <a href="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml"><img src="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml/badge.svg?branch=main" alt="tests workflow"></a>
</p>

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/dashboard-dark.webp"><img src="docs/readme/dashboard-light.webp" alt="The BioHop dashboard: clicks this week against last week, a setup checklist and a card for each bio page" width="100%"></picture></p>

<p align="center">One page for your links, embeds, socials and a QR code. On the hosted service it is published at <code>hel.ink/b/{slug}</code>.</p>

<p align="center">
  <a href="https://bio.hel.ink">bio.hel.ink</a> ·
  <a href="#install">Install</a> ·
  <a href="#features">Features</a> ·
  <a href="#run-your-own">Run your own</a> ·
  <a href="#privacy">Privacy</a> ·
  <a href="#faq">FAQ</a> ·
  <a href="#security">Security</a> ·
  <a href="#license">License</a>
</p>

---

<a id="install"></a>

## Install

One command. It checks your computer first, asks a few questions and can set up the HOP ID hub for you:

```bash
curl -fsSL https://raw.githubusercontent.com/navi-crwn/BioHop/main/install.sh | bash
```

From a clone: `./install.sh`. Update with `./update.sh`, remove with `./uninstall.sh`. Add `--yes` to take every default; `--help` lists the options. The installer needs PHP 8.3 or newer, Composer, Node 20 or newer and about 1 GB of free disk space. `--dry-run` prints the steps without running them.

> [!NOTE]
> BioHop keeps its accounts in a HOP ID hub ([HEL.ink](https://github.com/navi-crwn/HEL.ink)). There is no hub-free mode. The installer sets up a hub next to BioHop (the default, about a minute) or connects to one you already run. To install the hub and several apps in one go, use `install-family.sh` from the HEL.ink repository.

## What BioHop is

BioHop is a link-in-bio app. You build one page from links, pictures, embeds and social icons, pick a theme and publish it. The hosted service at [bio.hel.ink](https://bio.hel.ink) is the dashboard, and the public pages live at `hel.ink/b/{slug}`. On your own server a page is served at `{PUBLIC_URL}/b/{slug}`.

There is no registration or password in BioHop. You sign in once with your HOP ID at the hub, and the app knows you. BioHop started as the bio half of an app that is now [LinkHop](https://github.com/navi-crwn/LinkHop), the URL shortener. The plan for that split is in [`docs/planning/PLAN-pemisahan-aplikasi.md`](docs/planning/PLAN-pemisahan-aplikasi.md) (Phase D is this repository).

The screenshots below come from a local install with made-up sample data (a ceramics studio page). They show no real people or accounts.

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/editor-dark.webp"><img src="docs/readme/editor-light.webp" alt="The page editor: the list of blocks with a Quick Add panel on the left and a live phone preview of the page on the right" width="100%"></picture></p>

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/analytics-dark.webp"><img src="docs/readme/analytics-light.webp" alt="Page analytics for 30 days: views, link clicks, click rate, clicks over time, devices, top links, top countries and how visitors arrived" width="100%"></picture></p>

<p align="center">
  <img src="docs/readme/public-page.webp" alt="A public bio page on a phone: profile, a status chip, a Links and Shop tab bar, three buttons and an FAQ" width="32%">
  &nbsp;
  <img src="docs/readme/public-shop.webp" alt="The Shop tab of the same page: two products in a two-column grid, one of them marked Sold out" width="32%">
</p>

<a id="features"></a>

## Features

### Pages and editor

- A public page at `/b/{slug}` with a QR code (`/b/{slug}/qr` downloads a PNG) and an installable web app manifest.
- A six-step wizard for a new page: address, goal, platforms, first links, profile and colours, with a live phone. A plain form stays available at `?classic=1`.
- Import from a public Linktree page into a draft page.
- The editor at `/bio` is one page that scrolls, with a live preview beside it. It saves by itself about a second after you stop typing and sends only what changed. `Ctrl/Cmd+S` saves at once and `Ctrl/Cmd+K` opens a command palette.
- Drag to reorder. A select mode shows, hides, archives or deletes several blocks together. An archived block leaves the page but keeps its clicks.
- Versions: the editor saves a version before each editing session. You can also name a version and restore any of them.
- Pages can be duplicated. By default an account has 3 pages and a page has 100 blocks (see the settings below).
- Design: four header layouts (classic, hero, banner with a cover picture, cutout), 17 theme presets in the picker, wallpaper as colour, gradient or picture, button and block styles, fonts served from the app and custom CSS per page.
- The interface is available in English, 简体中文, Deutsch, Bahasa Indonesia, 日本語, Português (Brasil), Español, Français and Русский.

### Blocks

BioHop offers 19 block kinds when you add a block: link, text, header, image, divider, YouTube, music (Spotify, SoundCloud, Apple Music or YouTube Music), audio clip, picture carousel, PDF, product, collection, goal bar, receive payment, sign-up form, countdown, contact card, location and FAQ. Blocks of five retired kinds (WhatsApp rotator, app store link, SoundCloud, RSS feed, poll) that a page already has keep working and can be edited, but cannot be added again.

- The WhatsApp rotator block is retired: a page that has one keeps it (it rotates through up to 10 numbers, in turn or by weight), and no new one can be made.
- An app block sends the visitor to the store that matches their system.
- Any block can have a publish and an expiry time.
- A link can send visitors elsewhere by country, device, system or hour of the day, and it can have its own PIN.
- A collection groups the blocks under it, stacked or as a two-column grid.
- Players and maps load from YouTube, Spotify and the other hosts only when the visitor taps them.

### Shop

A product block has a picture, name, price with currency, a was-price, variants with their own price and stock, a stock count and a sold-out switch. It can fill its details in from the shop's address. When a page has a product, the public page shows a Links and Shop tab bar, and the shop has its own address at `/b/shop/{slug}`. BioHop takes no payments: a product opens its own address in a new tab and counts as a click.

### Page settings

Password, content warning (none, sensitive or adult 18+), unlisted, privacy mode (no counting at all), an address to send visitors to while the page is unpublished, a status chip, a floating button, a footer line, a share picture, a favicon, SEO title and description, optional UTM parameters on outgoing links, and the page owner's own Google Analytics, Tag Manager, Facebook or TikTok pixel (see [Privacy](#privacy)).

### Forms and webhooks

A sign-up form block collects a name, an e-mail address and a phone number, and the visitor must tick a consent box that names the page. The owner downloads the entries as CSV, deletes them, and sets how many days they are kept (1 to 365, 90 by default). A page can also send each new entry as signed JSON to an address the owner chooses. The scheduler retries failed deliveries, and the editor shows a delivery log with a test button and a resend button.

### Analytics

Per page: views, link clicks, click rate, clicks over time, devices, countries, top links, referring sites, recent clicks and how visitors arrived (the link itself, the QR code, an NFC tag with `?src=nfc`, or the installed web app). Bot clicks are not counted, a repeat click from the same visitor within 10 minutes is not counted, and one address counts at most 20 clicks per block in 10 minutes. See [Privacy](#privacy) for what the click log holds.

### Short links

"Shorten this URL" in a block calls the LinkHop API with the member's own service token (scope `link:create`, created on first use and kept in `bio_linkhop_tokens`). If LinkHop cannot be reached, the block keeps its original address and the editor says so. A link to hel.ink or to a HOP app is kept as typed.

### Safety and operations

- `abuse:scan` checks bio pages and blocks with the rules and the shared `domain_blacklist` from `hop/safety`. It runs hourly over what changed and once a day over everything. Google Safe Browsing, abuse.ch URLhaus and Spamhaus DBL lookups are optional.
- Visitors can report a page at `/report-abuse` (Cloudflare Turnstile protects the form when its keys are set).
- Admin work happens in Imperium, the HOP control panel. BioHop answers its signed `/api/hop/*` requests, and `/admin` sends admins there.
- Uploads are refused with a 507 when the disk is nearly full (see [`docs/UPLOAD-STORAGE.md`](docs/UPLOAD-STORAGE.md)). Uploaded pictures are shrunk to WebP. SVG is taken only for custom block icons and the QR logo, and is rebuilt from an allowlist before it is stored; the profile picture, images, cover, background, share picture and favicon take PNG, JPG, GIF, WebP or BMP.
- The feed reader and the webhook sender connect to the addresses they checked, so a name cannot resolve to a different address between the check and the request.
- The content security policy names no CDN, font or icon host. Libraries are bundled with the app.
- The home page at bio.hel.ink is an interactive tour with the official HEL.ink page in a phone. With "reduce motion" on, every scene shows its finished state.

<a id="run-your-own"></a>

## Run your own

You need PHP 8.3 or newer, Composer and Node 20 or newer. The shared packages `hop/auth`, `hop/ui` and `hop/safety` come from their GitHub repositories (`navi-crwn/hop-auth`, `hop-ui` and `hop-safety`, listed as `vcs` repositories in `composer.json`). The installer above does all of this for you. To do it by hand:

```bash
git clone https://github.com/navi-crwn/BioHop.git
cd BioHop
composer install
cp .env.example .env && php artisan key:generate
```

Open `.env` before you migrate. `.env.example` documents every key. Set at least these:

| Key | What it is |
|---|---|
| `APP_URL`, `PUBLIC_URL` | The dashboard address and the address public pages are served under |
| `ASSET_URL` | Where uploads and built files are served; empty means `APP_URL` |
| `HOP_HUB_URL` | Your hub's address (HOP ID sign-in goes there) |
| `HOP_APP` | This app's slug at the hub: `biohop` |
| `HOP_SSO_SECRET` or `HOP_SSO_SECRET_BIOHOP` | The SSO secret shared with the hub, 32 or more characters |
| `HOP_RECHECK`, `HOP_RECHECK_MAX_AGE` | The silent account re-check of `hop/auth` (on by default, 300 seconds): the home page, the info pages and every signed-in page confirm with the hub that the account in this session is still the one signed in there. `HOP_RECHECK=false` turns it off; public bio pages never do it |
| `LINKHOP_URL` | Your LinkHop, for "shorten this URL" (`LINKHOP_ENABLED=false` turns it off) |
| `DB_CONNECTION`, `DB_*`, `DB_MIGRATIONS_TABLE` | Database settings. `DB_MIGRATIONS_TABLE=migrations_bio` keeps this app's migration log apart from the hub's |
| `TRUSTED_PROXIES` | `cloudflare`, `*` or a list of proxy addresses, when you run behind a proxy |
| `BIO_MAX_PAGES`, `BIO_MAX_BLOCKS`, `BIO_SLUG_MIN_LENGTH` | Pages per account (3), blocks per page (100) and the shortest address a member can claim (5) |
| `BIO_ALLOW_CUSTOM_CSS` | Set to `false` to refuse custom CSS and stop sending existing CSS to visitors |
| `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` | Cloudflare Turnstile for the abuse report form. In production a missing secret makes the form refuse submissions |

Optional keys cover GeoIP (`GEOLITE_CITY_DB`, `GEOLITE_ASN_DB`, `GEOIP_WEB_PROVIDERS`, `IPINFO_API_KEY`, `ABSTRACTAPI_KEY`), link reputation lookups (`SAFE_BROWSING_KEY`, `URLHAUS_AUTH_KEY`, `SPAMHAUS_DBL`), upload headroom (`BIO_UPLOAD_MIN_FREE_MB`, `BIO_UPLOAD_MIN_FREE_PERCENT`), the edge cache purge (`CLOUDFLARE_ZONE_ID`, `CLOUDFLARE_API_TOKEN`, see below) and the official page the home page shows (`HOP_OFFICIAL_BIO`, `HOP_OFFICIAL_VIDEO`, `HOP_OFFICIAL_MUSIC`, `HOP_OFFICIAL_CONTACT`). An operator can also change the shortest address, the pages per account, the blocks per page and the custom CSS switch at run time from Imperium.

```bash
php artisan migrate
php artisan storage:link
npm install && npm run build
php artisan serve        # http://localhost:8000
```

### Keep it running

BioHop needs a queue worker and the scheduler. In production run `php artisan queue:work --tries=3` and `php artisan schedule:work` under systemd or supervisor, or use one cron line: `* * * * * cd /path/to/BioHop && php artisan schedule:run`. The installer's `start.sh` starts the web server, the worker and the scheduler together for a trial on one machine.

The scheduler runs these commands:

| Command | When | What it does |
|---|---|---|
| `abuse:scan --since=2h` | hourly | Scans pages and blocks that changed |
| `abuse:scan --full --purge` | daily, 04:10 | Scans everything and deletes items disabled for abuse for longer than the purge period |
| `abuse:feeds` | daily, 02:15 | Refreshes the threat feeds from `hop/safety` |
| `privacy:prune` | daily, 03:30 | Deletes click logs older than 30 days, form entries past their retention and webhook deliveries older than 7 days |
| `bio:webhooks:retry` | every minute | Sends webhooks whose next try is due |

Other commands, run by hand: `bio:official-page` fills the official HEL.ink page from the shared manifest, `bio:optimize-images` converts old uploads to WebP, and `bio:unwrap-family-links` restores links to HOP sites that were wrapped in short links. Each of the three only reports unless you pass `--apply`.

### Behind Cloudflare: public pages from the edge

A public bio page is built for every request. Served from one place, that is fast near the server and slow far from it: measured from the origin the page answers in 36 to 175 ms, while a visitor in Asia, whose request travels through a Cloudflare edge to Germany, waits 0.3 to 3.2 s. BioHop therefore lets an edge keep a plain page for a minute. A published page without a password, content warning, form or poll, asked for by a visitor with no session, no sign-in and no chosen language, is sent with `Cache-Control: public, s-maxage=60, max-age=0, stale-while-revalidate=300` and `Vary: Cookie`, and with no cookie. Every other response stays `no-cache, private`. The visit is counted by a small request from the page (`POST /b/{slug}/view`, no JavaScript framework) with the same once-per-visitor rules as a click, so a page that came from the edge still counts; a page the origin serves itself counts the visit as before.

Cloudflare caches HTML only when a Cache Rule says so. Create one for the zone:

- **If**: hostname is `hel.ink` or `bio.hel.ink` (your `PUBLIC_URL` and `APP_URL`), URI path starts with `/b/`, and the Cookie header does not contain `biohop_session` (your `SESSION_COOKIE`), `hop_lang` or `remember_web`.
- **Then**: cache eligibility *Eligible for cache*; Edge TTL *Use cache-control header if present, bypass cache if not*; Browser TTL *Respect origin*.

A rule that bypasses the cache whenever any cookie is present is also correct, only visitors who have clicked a block (`hel_vid`) or picked a language then skip the cache too. Cloudflare does not honour `Vary: Cookie`, so the cookie condition in the rule is what keeps a signed-in person's or a translated page apart from the shared copy.

So that a change shows at once and not after the minute, set `CLOUDFLARE_ZONE_ID` and `CLOUDFLARE_API_TOKEN` (an API token with *Zone → Cache Purge* for that zone). Saving a page, publishing or unpublishing it, or adding, changing or removing a block then queues a purge by URL of the page, its shop and its QR, NFC and installed-app entries on both hosts (`PurgeEdgeCache`, one job per save; needs the queue worker). A failed purge is logged and nothing else; the save never waits for Cloudflare. With the two keys empty only the headers are sent and a change shows when the minute is over.

### Database

BioHop is designed to share one database with the hub and LinkHop. Each app keeps its own migration log, `migrations_bio` here. Run the hub's migrations first, so the tables the other apps own (`users`, `roles`, `api_tokens`, `ip_bans`, `domain_blacklist` and `abuse_flags` among them) exist. When you install with `--hub new` on SQLite, BioHop uses the hub's database file. This design is documented, but compatibility between the apps' releases is not checked here, so test it before you point BioHop at a database you care about.

### Update and remove

`./update.sh` first copies the database to `backups/` (SQLite with `sqlite3 .backup`, MySQL with `mysqldump`; the copy is readable only by you). It then puts the site in maintenance mode, pulls the new code, installs packages, builds the front end, migrates and tells the queue workers to restart. If a step fails before the migration, it restores the previous code. If it fails at or after the migration, it leaves the site in maintenance mode and prints the manual steps. `--no-backup` skips the copy and `--force` updates even when you changed tracked files. `./uninstall.sh` keeps your data; `--purge` also deletes `.env`, `storage/` and `backups/` after you type the site name.

### Tests

Tests run on in-memory SQLite. The tables other apps own are created by `database/migrations/testing/`, and only when the test suite runs. CI runs `composer audit`, Pint and the tests on PHP 8.3.

```bash
php artisan test
vendor/bin/pint --test
```

## Sign-in

Sign in with your HOP ID at the hub. Opening `/bio` sends you to the hub and back, which needs `HOP_HUB_URL`, `HOP_APP=biohop` and the SSO secret to be set first. Signing out ends your session here and then at the hub. E-mail verification and account settings are handled at the hub. Public bio pages at `/b/{slug}` need no sign-in, but BioHop is built to run together with the hub and is not a standalone app.

<a id="privacy"></a>

## Privacy

This is what the code does today.

- The click log keeps the time, the block, the country, the device type, the browser, the referring site and how the visitor arrived. It keeps a keyed hash of the visitor's IP address (HMAC-SHA256 with `APP_KEY`, shortened to 40 characters), never the address itself. `privacy:prune` deletes click logs after 30 days. Poll votes and form entries also store only a keyed hash of the address.
- To find the country, the visitor's address is looked up in a local GeoIP database if you have installed one, and otherwise sent, as the plain address, to the first web geolocation service that answers (see [`CREDITS.md`](CREDITS.md)). Set `GEOIP_WEB_PROVIDERS=false` to stop that; the country then stays empty without the local database.
- To count a page view once a day, to cap clicks per address and to remember a ban or a geolocation answer, the app uses cache keys that contain the same keyed hash of the address, never the address itself (24 hours for views and geolocation answers, up to 10 minutes for click and password limits).
- A random cookie, `hel_vid`, lasts one year and is set on a click. It only helps de-duplicate click counts. A first-time reader of a plain public page gets no session and no cookies. Signed-in members have session rows that include their address, as with any Laravel database session.
- Privacy mode on a page stops view and click counting for that page.
- Abuse reports store the reporter's e-mail address, the reason and the reporter's IP address in the shared `abuse_reports` table. This repository does not prune that table.
- BioHop adds no advertising or analytics scripts of its own. Fonts, icons and flags are served from the app, so a visitor does not contact Google or a CDN.
- A page owner can add Google Analytics, Google Tag Manager, a Facebook pixel or a TikTok pixel to their page. The page owner chooses to add them and is responsible for them; BioHop only provides the platform. They load only after the visitor agrees in a banner on the page, and the visitor's choice is remembered in that browser. When they load, the visitor's browser sends data to those services under their own privacy policies. BioHop does not receive that data.
- The visitor must tick a consent box to send a sign-up form. Entries are stored for the page owner, who sets the retention period, can export them as CSV and can delete them. They are deleted with the page or the account.

<a id="faq"></a>

## FAQ

**Can I use BioHop without the hub?** No. Editing needs a HOP ID at the hub, and there is no local registration or password. Public pages need no sign-in, but the app is built to run together with the hub.

**What happens to my click data?** It is kept for 30 days and then deleted. The click log holds a keyed hash of the address, not the address. See [Privacy](#privacy) for the details.

**Does the safety scan run by itself?** Yes, once the scheduler runs. It uses shared rules and a shared blacklist. No automated scan catches everything.

**Can I export my pages?** Yes. `/settings/export` returns your pages and blocks as JSON. It does not include click logs or form entries; form entries have their own CSV download.

**Can I self-host it?** Yes, under the MIT license. You bring your own hub, a LinkHop if you want short links, and a database. See [Run your own](#run-your-own).

<a id="family"></a>

## Self-hosting

<!-- self-hosting:start -->
Everything BioHop needs is in public repositories: no private repository, no token and no hel.ink account. The family guide has the details: [docs/self-hosting.md](https://github.com/navi-crwn/HEL.ink/blob/main/docs/self-hosting.md).

| Piece | For BioHop | What it does |
| --- | --- | --- |
| [HEL.ink](https://github.com/navi-crwn/HEL.ink) (hub, HOP ID) | **Required**: the accounts live there, in the same database | Accounts and sign-in for every app |
| [hop/auth](https://github.com/navi-crwn/hop-auth), [hop/ui](https://github.com/navi-crwn/hop-ui), [hop/safety](https://github.com/navi-crwn/hop-safety) | Installed by `composer install` from public GitHub | Shared PHP code: sign-in, interface, link safety |
| [Imperium](https://github.com/navi-crwn/Imperium) | Optional, but the admin pages of BioHop live there | Admin panel for the whole family; needs the hub |
| Family fonts and tiles | Nothing to install: loaded from `hel.ink/hop-assets` | Without hel.ink: system fonts, no floating tiles |

- **Install order**: the [hub](https://github.com/navi-crwn/HEL.ink) first, then BioHop. `./install.sh --hub new` installs both.
- **Minimum settings**: `APP_URL`, `PUBLIC_URL`, `HOP_APP=biohop`, `HOP_HUB_URL`, `HOP_SSO_SECRET` (the hub's), `DB_*` (the hub's database), `HOP_TRUSTED_HOSTS` (your host names); with Imperium: `HOP_CONTROL_URL`.
- **Alone or in the family**: BioHop does not run without a hub. In the family it shares the hub's database and `HOP_SSO_SECRET`.
- **Without Imperium**: `/admin` shows a notice. Manage accounts in the hub's own admin pages, and use the signed `/api/hop/*` API and the artisan commands.
<!-- self-hosting:end -->

## The HOP family

BioHop is part of the **HOP** family of self-hostable open-source apps behind [hel.ink](https://hel.ink).

<!-- hop-family:start -->
<!-- Generated from HEL.ink resources/hop-assets/apps.source.json; edit there, then run scripts/hop_assets.py build. -->
| App | Address | Repository | What it does |
| --- | --- | --- | --- |
| **HOP ID** | [hel.ink](https://hel.ink) · accounts at `hub.hel.ink` | [navi-crwn/HEL.ink](https://github.com/navi-crwn/HEL.ink) | One account for every HOP app. |
| **LinkHop** | [link.hel.ink](https://link.hel.ink) · `hel.ink/l/…` | [navi-crwn/LinkHop](https://github.com/navi-crwn/LinkHop) | Short links, QR codes and click analytics. |
| **BioHop** | [bio.hel.ink](https://bio.hel.ink) · `hel.ink/b/…` | [navi-crwn/BioHop](https://github.com/navi-crwn/BioHop) | One link-in-bio page for everything you do. |
| **PixelHop** | [pixel.hel.ink](https://pixel.hel.ink) · `p.hel.ink/…` | [navi-crwn/PixelHop](https://github.com/navi-crwn/PixelHop) | Image hosting with albums and direct links. |
| **NoteHop** | [note.hel.ink](https://note.hel.ink) · `n.hel.ink/…` | [navi-crwn/NoteHop](https://github.com/navi-crwn/NoteHop) | Encrypted notes you share with a link. |
| **FileHop** | [file.hel.ink](https://file.hel.ink) | [navi-crwn/FileHop](https://github.com/navi-crwn/FileHop) | Convert files in your browser; nothing is uploaded. |
| **PayHop** | [pay.hel.ink](https://pay.hel.ink) | [navi-crwn/PayHop](https://github.com/navi-crwn/PayHop) | Make a payment QR and split a bill; nothing leaves your browser. |
| **Imperium** | not public-facing: optional, for admins | [navi-crwn/Imperium](https://github.com/navi-crwn/Imperium) | The HOP control panel. |
<!-- hop-family:end -->

<a id="security"></a>

## Security

Found a vulnerability? Report it privately through GitHub's security advisories for this repository: [github.com/navi-crwn/BioHop/security/advisories](https://github.com/navi-crwn/BioHop/security/advisories). Please don't open public issues for security reports, and don't post secrets, tokens or exploit details in public places. [SECURITY.md](SECURITY.md) has the details.

## Changelog and credits

Changes are listed in [CHANGELOG.md](CHANGELOG.md). The third-party software, fonts, icons and services BioHop uses or ships, with their licenses, are listed in [CREDITS.md](CREDITS.md).

<a id="license"></a>

## License

[MIT](LICENSE), like the rest of the HOP family. Third-party licenses are listed in [CREDITS.md](CREDITS.md).

---

<p align="center"><sub>BioHop · part of the HOP family · <a href="https://hel.ink">hel.ink</a></sub></p>
