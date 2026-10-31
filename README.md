<p align="center">🌐 <b>English</b> · <a href="README.zh.md">简体中文</a> · <a href="README.es.md">Español</a> · <a href="README.ar.md">العربية</a> · <a href="README.pt.md">Português</a> · <a href="README.fr.md">Français</a> · <a href="README.ja.md">日本語</a> · <a href="README.id.md">Bahasa Indonesia</a></p>

# BioHop

<p align="center">
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="MIT license"></a>
  <a href="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml"><img src="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml/badge.svg?branch=main" alt="tests workflow"></a>
</p>

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/hero-en-dark.webp"><img src="docs/readme/hero-en-light.webp" alt="BioHop: one page with your links, embeds, socials and a QR code" width="100%"></picture></p>

<p align="center"><b>One page with your links, embeds, socials and a QR code — published at <code>hel.ink/b/{slug}</code>.</b></p>

<p align="center">
  <a href="https://bio.hel.ink">bio.hel.ink</a> ·
  <a href="#run-your-own">Run your own</a> ·
  <a href="#features">Features</a> ·
  <a href="#faq">FAQ</a> ·
  <a href="#security">Security</a> ·
  <a href="#license">License</a>
</p>

---

## What is BioHop?

BioHop is a link-in-bio app: you build one page with your links, images, embeds and socials, pick a theme, and publish it at `hel.ink/b/{slug}` — with a QR code ready to share.

It is the bio half of what used to be one app (now [LinkHop](https://github.com/navi-crwn/LinkHop), the URL shortener). Accounts live at the hub ([HEL.ink](https://github.com/navi-crwn/HEL.ink)): there is no registration or password here — you sign in once with your **HOP ID** and the app knows you. The plan behind the split is documented in [`docs/planning/PLAN-pemisahan-aplikasi.md`](docs/planning/PLAN-pemisahan-aplikasi.md) (Phase D is this repo).

<a id="features"></a>

## Features

- **Public bio pages at `hel.ink/b/{slug}`, each with a QR code and click tracking.**
- **An editor at `/bio` for pages, links, image/avatar/icon/QR-logo uploads, live preview, duplication and per-page analytics.**
- **Theme presets (light, dark, minimal, neon and many more) shared by the editor, its preview and the public page.**
- **No local sign-up or passwords: you sign in once with your HOP ID at the hub.**
- **Click analytics with country (GeoIP) plus device, browser and bot detection.**
- **"Shorten this URL" in blocks via LinkHop's API with the member's own service token; if LinkHop is down the block keeps its original URL.**
- **URL/content safety: `abuse:scan` with the shared `domain_blacklist` (hop/safety), scheduled hourly.**
- **Daily click-data pruning (`privacy:prune`).**

<a id="run-your-own"></a>

## Run your own

You need PHP 8.3, Composer and Node 20+. The shared packages `hop/auth`, `hop/ui` and `hop/safety` come from their GitHub repos (`navi-crwn/hop-auth`, `hop-ui`, `hop-safety`, branch `main`, listed as `vcs` repositories in `composer.json`).

```bash
git clone git@github.com:navi-crwn/BioHop.git
cd BioHop
composer install
cp .env.example .env && php artisan key:generate
```

**Configure before you migrate.** Open `.env` (`.env.example` documents every key; the comments there are the reference) and set at least:

| Key | What it is |
|---|---|
| `APP_URL` / `PUBLIC_URL` | This app's address and the public address pages are served under |
| `HOP_HUB_URL` | Your hub's address (HOP ID sign-in goes there) |
| `HOP_APP` | This app's slug at the hub: `biohop` |
| `HOP_SSO_SECRET` or `HOP_SSO_SECRET_BIOHOP` | The SSO secret shared with the hub, 32+ characters |
| `LINKHOP_URL` | Address of your LinkHop (enables "shorten this URL" in blocks) |
| `DB_CONNECTION`, `DB_*`, `DB_MIGRATIONS_TABLE` | Database settings; `DB_MIGRATIONS_TABLE=migrations_bio` keeps this app's migration log separate |

```bash
php artisan migrate      # logs only in migrations_bio
php artisan storage:link
npm install && npm run build
php artisan serve        # http://localhost:8000
```

**Database.** BioHop is designed to share one MySQL database with the hub and LinkHop (each app keeps its own migration log: `migrations_bio` here). Run the hub's migrations first, so tables like `users`, `roles`, `api_tokens` and `domain_blacklist` exist. This is the documented design; migration compatibility between the apps' releases is not verified in this README, so check it before pointing BioHop at a database you care about.

**Tests** run on sqlite in memory; the tables other apps own are created by `database/migrations/testing/` (tests only).

```bash
php artisan test
vendor/bin/pint --test
```

## Sign-in (HOP ID)

Sign in with your HOP ID at the hub — no registration or password here. Sign-in and the editor use standard SSO, which requires the `HOP_HUB_URL`, `HOP_APP=biohop` and SSO secret settings above to be in place before you log in. Opening `/bio` sends you to the hub and back. Signing out ends your session here and redirects to the HOP ID logout.

Public bio pages (`/b/{slug}`) don't require sign-in. BioHop is designed to run together with the hub — it is not a standalone app.

<a id="faq"></a>

## FAQ

**What happens to my click data?** Click tracking exists to show you how your page performs, and the `privacy:prune` command removes click data daily. No long-term click archive is kept on purpose.

**Does the safety scan run by itself?** Yes. `abuse:scan` is scheduled hourly and checks bio pages and blocks against the shared `domain_blacklist` from hop/safety. These are mechanisms, not guarantees: no automated scan promises to catch everything.

**Can I use BioHop without the hub?** No. Editing requires a HOP ID at the hub, and there is no local registration or password. Public bio pages don't require sign-in, but the app is designed to run together with the hub, and BioHop can't promise that public routes never contact the hub.

**Can I self-host it?** Yes — MIT licensed, and the section above is the whole quickstart. You bring your own hub, LinkHop and database.

<a id="family"></a>

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
| **Imperium** | private (owner only) | private | The HOP control panel. |
<!-- hop-family:end -->

<a id="security"></a>

## Security

Found a vulnerability? Please report it privately through GitHub's security advisories for this repo: [github.com/navi-crwn/BioHop/security/advisories](https://github.com/navi-crwn/BioHop/security/advisories). Please don't open public issues for security reports, and don't post secrets, tokens or exploit details in public places. See [SECURITY.md](SECURITY.md) for details.

<a id="license"></a>

## License

[MIT](LICENSE) — like the rest of the HOP family. Third-party licenses are listed in the [LICENSE](LICENSE) file.

---

<p align="center"><sub>BioHop · part of the HOP family · <a href="https://hel.ink">hel.ink</a></sub></p>
