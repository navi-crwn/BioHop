# BioHop

Link-in-bio pages for the **HOP** family of apps behind [hel.ink](https://hel.ink). One page with your links, embeds, socials and a QR code, published at `hel.ink/b/{slug}` and managed at `bio.hel.ink`.

BioHop is the bio half of what used to be one app (now [LinkHop](https://github.com/navi-crwn/LinkHop), the shortener). Accounts live at the hub ([HEL.ink](https://github.com/navi-crwn/HEL.ink), `hub.hel.ink`): there is no registration or password login here, you sign in once with your HOP ID and every app knows you. The plan behind the split is in [`docs/planning/PLAN-pemisahan-aplikasi.md`](docs/planning/PLAN-pemisahan-aplikasi.md) (Fase D is this repo).

### HOP Family Ecosystem

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

## What is here

| Piece | Where | Notes |
| --- | --- | --- |
| Bio pages, blocks, themes, uploads, analytics | `packages/bio` (`hop/bio`) | Owned by this repo. Public routes `/b/{slug}`, `/b/{slug}/qr`, `/b/click/{id}`; editor under `/bio`. |
| Sign-in (HOP ID) | `hop/auth` | `/auth/sso` → hub → `/auth/sso/callback` (`Auth::loginUsingId`). `/logout` ends the session here, then at the hub. |
| Cross-app navigation | `hop/ui` | `<x-hop::nav-items />` in the sidebar, from `config/hop.php`. |
| URL/content safety | `hop/safety` | Same rules and the same shared `domain_blacklist` as LinkHop. `abuse:scan` for bio pages and blocks. |
| Short links in blocks | `app/Bio/LinkHopClient.php`, `LinkHopShortlinks.php` | "Shorten this URL" calls LinkHop's API with the member's own service token (scope `link:create`, made on first use). If LinkHop is down the block keeps its original URL and the editor says so. |
| GeoIP, user agents | `app/Services` | Country of a click (local MaxMind DB first, then free web APIs) and device/browser/bot detection. |
| Admin | `/admin/bio` | List, inspect, monitor, export, unpublish, delete. For the HOP owner, global admins and `admin:biohop`. |
| Housekeeping | `routes/console.php` | `abuse:scan` hourly (+ daily `--full --purge`), `privacy:prune` (bio clicks older than 30 days), `abuse:feeds` (threat feeds). |

## Running it locally

You need PHP 8.3, Composer and Node 20+. The shared packages `hop/auth`, `hop/ui` and `hop/safety` come from their GitHub repos (`navi-crwn/hop-auth`, `hop-ui`, `hop-safety`, branch `main`, as `vcs` repositories in `composer.json`).

```bash
git clone git@github.com:navi-crwn/BioHop.git
cd BioHop
composer install
cp .env.example .env && php artisan key:generate
```

**Database.** BioHop shares one database with the hub and LinkHop. Point `DB_*` at the same database the hub uses (locally: run the hub's migrations first, so `users`, `roles`, `api_tokens`, `domain_blacklist`, … exist), then:

```bash
php artisan migrate      # logs only in migrations_bio; creates the bio tables when they are missing
php artisan storage:link # uploads live on the public disk (storage/app/public/bio-*)
npm install && npm run build
php artisan serve        # http://localhost:8000
```

**Sign-in.** Set `HOP_HUB_URL` to your local hub, `HOP_APP=biohop`, and the same `HOP_SSO_SECRET` the hub has (or its `HOP_SSO_SECRET_BIOHOP`). Opening `/bio` sends you to the hub and back. `SUPERADMIN_EMAIL` makes that account an admin here too.

**Public pages.** With nginx in front (production), `hel.ink/b/*` is routed here, so `PUBLIC_URL=https://hel.ink` while `APP_URL=https://bio.hel.ink`. Locally leave both at the same address.

**Tests** run on sqlite in memory; the tables other apps own are created by `database/migrations/testing/` (tests only).

```bash
php artisan test
vendor/bin/pint --test
```

## Shared database: who owns what

| Tables | Migrations run by | Log table |
| --- | --- | --- |
| users, roles, api_tokens, sessions, cache, jobs, … | hub | `migrations_hub` |
| links, link_clicks, domain_blacklist, ip_bans, abuse_flags, abuse_reports, … | LinkHop | `migrations_link` |
| bio_pages, bio_links, bio_clicks, bio_linkhop_tokens | **BioHop** | `migrations_bio` |

Rules this app follows:

- `App\Models\User` only reads the shared `users` table. The one exception is `abuse:scan` suspending a repeat offender (`status`), the same column the hub sets.
- `ip_bans` and `domain_blacklist` are read; findings of `abuse:scan` go to the shared `abuse_flags` (LinkHop's table, read by the hub's control center too).
- The bio migrations in `packages/bio/database/migrations` are the ones LinkHop already ran in production. A baseline migration (`0000_00_00_000000_baseline_existing_bio_tables`) records them as done in `migrations_bio` when `bio_pages` already exists, and every one of them checks (`hasTable`/`hasColumn`) before it creates or adds anything, so `migrate` on the production database is a no-op for the old tables.

## Installing on the server

Layout agreed for the HOP family: `/var/www/helink/hub`, `/var/www/helink/linkhop`, `/var/www/helink/biohop`; nginx on the same machine routes `hel.ink/b/*` here, `/l/*` and `/api/*` to LinkHop, the rest to the hub; `bio.hel.ink` is this app's dashboard. Cloudflare (free) stays in front for DNS, TLS and WAF.

```bash
# 1. Code
sudo -u www-data git clone git@github.com:navi-crwn/BioHop.git /var/www/helink/biohop
cd /var/www/helink/biohop
/usr/bin/php8.3 /usr/local/bin/composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 2. Environment: same DB_* as LinkHop/hub, APP_URL=https://bio.hel.ink, PUBLIC_URL=https://hel.ink,
#    HOP_HUB_URL=https://hub.hel.ink, HOP_SSO_SECRET (= the hub's HOP_SSO_SECRET_BIOHOP or HOP_SSO_SECRET),
#    LINKHOP_URL=https://link.hel.ink, TRUSTED_PROXIES=cloudflare, SESSION_COOKIE=biohop_session
cp .env.example .env && nano .env
/usr/bin/php8.3 artisan key:generate
/usr/bin/php8.3 artisan storage:link
chown -R www-data:www-data storage bootstrap/cache

# 3. Database (shared): records the old bio migrations, creates bio_linkhop_tokens
/usr/bin/php8.3 artisan migrate --force
/usr/bin/php8.3 artisan migrate:status

# 4. Caches (repeat after every deploy)
/usr/bin/php8.3 artisan config:cache && /usr/bin/php8.3 artisan route:cache && /usr/bin/php8.3 artisan view:cache
```

**PHP-FPM 8.3.** A pool for this app (or the shared `www` pool), document root `/var/www/helink/biohop/public`. Needed extensions: pdo_mysql, mbstring, gd (QR codes; imagick optional), intl, bcmath, curl, zip.

**nginx** (sketch; the full router is in the hub's README):

```nginx
# bio.hel.ink: the dashboard
server {
    listen 443 ssl http2; server_name bio.hel.ink;
    root /var/www/helink/biohop/public; index index.php;
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ { include fastcgi_params; fastcgi_pass unix:/run/php/php8.3-fpm.sock; fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name; }
}
# hel.ink: inside the hub's server block, before the catch-all
location ^~ /b/ { root /var/www/helink/biohop/public; try_files $uri /index.php?$query_string; }
```

**Queue worker and scheduler** (systemd or supervisor; the queue is `database`):

```ini
# /etc/systemd/system/biohop-queue.service
[Service]
User=www-data
WorkingDirectory=/var/www/helink/biohop
ExecStart=/usr/bin/php8.3 artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
```

```cron
* * * * * www-data cd /var/www/helink/biohop && /usr/bin/php8.3 artisan schedule:run >> /dev/null 2>&1
```

**Uploads at cutover.** Bio images were uploaded by LinkHop to its public disk. Copy them once, before the router sends `/b/*` here (paths inside the disk are unchanged):

```bash
rsync -a /var/www/helink/linkhop/storage/app/public/bio-images/ /var/www/helink/biohop/storage/app/public/bio-images/
rsync -a /var/www/helink/linkhop/storage/app/public/bio-avatars/ /var/www/helink/biohop/storage/app/public/bio-avatars/
# any other storage/app/public/bio* folder LinkHop has, the same way
```

**GeoIP (optional).** Put `GeoLite2-City.mmdb` in `storage/app/` (or copy LinkHop's) for offline country lookups; otherwise the free web APIs are used within their quotas.

## Configuration

See `.env.example`; the comments there are the documentation. `config/hop.php` lists the apps of the family and must stay the same as the hub's copy (only `current` differs). `config/brands.php` and `packages/bio/config/bio-themes.php` hold the brand icons and themes of the editor.

## Deploy checklist

```bash
cd /var/www/helink/biohop && git pull
/usr/bin/php8.3 /usr/local/bin/composer install --no-dev --optimize-autoloader
npm ci && npm run build
/usr/bin/php8.3 artisan migrate --force
/usr/bin/php8.3 artisan config:cache && /usr/bin/php8.3 artisan route:cache && /usr/bin/php8.3 artisan view:cache
systemctl restart biohop-queue
```

## License

MIT, like the rest of the HOP family.
