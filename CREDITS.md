# Credits

BioHop is released under the MIT License (see `LICENSE`). This file lists the third-party software, fonts, icons and services
that this repository uses or ships, with their licenses. Versions are the ones locked in `composer.lock` and `package-lock.json`
at the time of writing; the lock files are the authoritative list, including indirect dependencies.

## PHP packages (Composer, runtime)

| Package | Version | License | Use |
|---|---|---|---|
| laravel/framework | 12.x | MIT | Application framework |
| laravel/tinker | 2.x | MIT | Console shell |
| simplesoftwareio/simple-qrcode | 4.2 | MIT | QR codes (uses bacon/bacon-qr-code, BSD-2-Clause) |
| symfony/html-sanitizer | 7.4 | MIT | Sanitising text blocks |
| matomo/device-detector | 6.5 | LGPL-3.0-or-later | Browser and device detection for click analytics. It is used as an unmodified library through Composer. |
| geoip2/geoip2 | 3.x | Apache-2.0 | Reader for local GeoIP databases (with maxmind-db/reader, Apache-2.0) |
| hop/auth, hop/ui, hop/safety | dev-main / 0.1.x | MIT | The HOP family packages (sign-in through the hub, shared interface, URL and content safety) |
| hop/bio | in this repository (`packages/bio`) | MIT | The bio page package |

Indirect dependencies are mostly MIT; the others are BSD-2-Clause, BSD-3-Clause (for example league/commonmark, nikic/php-parser,
vlucas/phpdotenv) and Apache-2.0 (for example phpoption/phpoption). nette/schema and nette/utils are offered under
BSD-3-Clause, GPL-2.0 or GPL-3.0; BioHop uses them under BSD-3-Clause.

## PHP packages (Composer, development and tests only; not part of a production install)

pestphp/pest and pestphp/pest-plugin-laravel (MIT), nunomaduro/collision (MIT), fakerphp/faker (MIT), mockery/mockery (BSD-3-Clause),
laravel/pail (MIT), laravel/pint (MIT).

## JavaScript packages (npm, bundled into `public/build` by Vite)

| Package | License | Use |
|---|---|---|
| alpinejs, @alpinejs/collapse | MIT | Interface behaviour |
| axios | MIT | Requests from the editor |
| chart.js (with @kurkle/color) | MIT | Analytics charts |
| qr-code-styling (with qrcode-generator) | MIT | Styled QR codes in the editor |
| sortablejs | MIT | Drag-and-drop ordering of blocks |
| lenis | MIT | Smooth scrolling on the home page |
| gsap | GreenSock "Standard no charge" license (https://gsap.com/standard-license) | Home page scroll animation. This is not an open-source license: read its terms before you redistribute a modified build. |

Build tools (not shipped to browsers): vite, laravel-vite-plugin, tailwindcss and @tailwindcss/forms and @tailwindcss/vite (MIT), concurrently (MIT).

All of these are bundled with the application. No script is loaded from a CDN, except the optional bot check described under
"Services".

## Fonts

The fonts offered in the page editor are served from this site (`public/fonts/bio`, Latin subset, built by
`scripts/build-bio-fonts.py` from Google Fonts). Visitors do not contact Google.

- Under the SIL Open Font License 1.1: DM Sans, Inter, Lato, Lexend, Manrope, Merriweather, Montserrat, Nunito, Open Sans, Oswald,
  Playfair Display, Plus Jakarta Sans, Poppins, Quicksand, Raleway, Source Sans 3, Space Grotesk, Work Sans.
- Roboto: Apache License 2.0 (newer releases are also published under the SIL OFL 1.1).
- Ubuntu: Ubuntu Font Licence 1.0.

Each font remains the work of its designers; the fonts are not sold on their own.

## Icons and images

- `public/images/brands`: brand logos drawn from Simple Icons (CC0 1.0 Universal). The brands and logos themselves are
  trademarks of their owners and are shown only to point to the matching account or service.
- `public/images/flags`: country flags from the flag-icons collection (MIT).
- `public/images/social-icons`, `public/images/quick-add`: simple single-path glyphs kept in this repository.
- `public/images/brand`, `public/images/demos`, `docs/assets`: the BioHop mark and screenshots of this application.

## Services the application can call

These are not bundled; each is called only when the matching feature is on. They are named so that operators know what leaves
the server.

- Cloudflare Turnstile (https://www.cloudflare.com/website-terms/): bot check on public forms such as abuse reports, only when
  the Turnstile keys are set. The browser loads Cloudflare's script for it.
- IP geolocation, tried after the local database (see `.env.example`): freeipapi.com, ipapi.co, ipinfo.io and
  abstractapi.com (all over https). Each has its own terms and free-tier limits. The visitor's address is sent to the one that
  answers; `GEOIP_WEB_PROVIDERS=false` turns this off.
- Google Safe Browsing, abuse.ch URLhaus and Spamhaus DBL: optional reputation checks of links, through hop/safety.
- A page owner's own analytics pixels (Google Analytics, Meta, TikTok, Google Tag Manager) load only when the owner enters an ID
  and the visitor consents.
- LinkHop's short link API and the HOP ID hub, both run by the same operator.

## Inspiration

BioHop's feature set was shaped by link-in-bio tools such as Linktree and LinkStack. No code or assets were copied from them.

## Typefaces for the app names

- **Pixelify Sans** (LinkHop) and **Space Grotesk** (BioHop), subset to the letters of the app names, SIL Open Font License 1.1. Licenses: `public/hop-brand/fonts/LICENSE-*.txt`.

## Corrections

If an attribution or a license is missing or wrong, please open an issue on the repository (https://github.com/navi-crwn/BioHop)
and it will be fixed.
