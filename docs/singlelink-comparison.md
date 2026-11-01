# BioHop next to Singlelink

Read on 2026-11-01 from github.com/singlelink-co/Singlelink (MIT). Two versions matter:

- **v3** (branch `v3`, 2020–2022): the product that ran singlelink.co. Node: Fastify API + PostgreSQL, a Nuxt editor, a renderer. This is the version worth comparing.
- **master / 4.x** (2023–): a rewrite for one person, one password: a `links` table (label, content, type, position), an `events` table (view/click), a dashboard with a link list and an analytics page. Link types: vanilla, image, YouTube, text, avatar, HTML. Nothing else yet.

## What Singlelink v3 has

| Area | Singlelink v3 |
|---|---|
| Accounts | e-mail + password, Google sign-in, password reset, e-mail notification settings, data package download, account deletion |
| Profiles | several profiles per account, handle, headline, subtitle, avatar by URL, social links (JSON), visibility unpublished / published / published-18+, privacy mode (no analytics), unlisted, custom domain field, watermark toggle |
| Links | link, image, YouTube, divider, HTML snippet; label + subtitle, custom CSS per link, drag reorder |
| Themes | user themes (colors JSON + custom CSS + custom HTML), global themes set by admins, custom CSS/HTML per profile |
| Marketplace | addons (theme, preset, plugin) with publish, tags, favorites, install/uninstall, stats, authored list |
| Analytics | page views and link clicks (30 days), total clicks, per link; recorded through `/analytics/.../record/:id` |
| Import | Linktree import (`/profile/linktree_import`) |
| Community | leaderboards (top profiles) |
| Admin | bans, permission groups, theme administration |
| Billing | subscription tiers in the schema (free, whale, enterprise); Mixpanel and SimpleFileUpload as optional providers |

## What BioHop has that Singlelink does not

- 18 block kinds (link, image, text, header, divider, spacer, video, YouTube, music, Spotify, SoundCloud, contact card, HTML, countdown, map, FAQ, code, e-mail) with icons, thumbnails, brand colours and per-block colours.
- 20+ built-in themes, backgrounds (colour, gradient, uploaded picture), fonts, button shapes and shadows, hover effects, background animation, custom CSS.
- 100+ social platforms with brand icons.
- Analytics with countries, devices, referrers and click rate, counted by keyed hash, no cookies; export of everything.
- Password gate and content warning / 18+ gate on the page itself.
- QR code with colours and a logo; short links through LinkHop; the HOP family sign-in (one HOP ID for every app, 2FA at the hub).
- Autosave with revisions, a live preview that is the real page, SEO fields, analytics pixels, abuse reports and scanning, eight languages, a public API, admin from Imperium.
- Several pages per HOP ID (`bio.max_pages_per_user`), duplicate a page.

## What Singlelink v3 has that BioHop lacks

| Gap | Worth it? | Note |
|---|---|---|
| **Linktree import** | yes, core | Most people arrive from Linktree. A paste-the-URL import that reads the public Linktree page (title, avatar, links, socials) into a draft page removes the biggest reason not to switch. |
| **Custom domain per page** | later | Needs DNS verification, TLS at the edge and nginx mapping; the hub decides. Keep in the backlog. |
| **Privacy mode** (no analytics at all) | yes, small | A switch that stops counting views and clicks for a page. Cheap, honest, fits HOP. |
| **Unlisted** | partly there | BioHop has `show_in_directory`, `allow_indexing` and `seo_noindex`; make the editor show one clear "Unlisted" switch that sets them together. |
| **User-made themes** shared across pages | medium | Save the current appearance as a named theme on the account, apply it to other pages. No marketplace: curation and abuse are not worth it for HOP. |
| Custom HTML per theme/profile | no | HTML blocks exist; page-level custom HTML widens the XSS surface. |
| Marketplace, leaderboards, subscriptions | no | Not HOP's model (no paid tiers, no community ranking). |
| Google sign-in, password reset, data package | already at the hub | HOP ID covers them; BioHop has `/settings/export`. |

## Stability and core work found while reading BioHop for this comparison

1. **Background picture upload did not save** (fixed 2026-11-01): the upload worked, the save refused the path. A test now covers the whole path.
2. The editor's appearance, profile and settings tabs are English only (no `__()`); the public pages are translated. Decide whether the editor joins the nine languages.
3. The link check (`hop-safety`) is applied field by field in the controller; a single validator for "address or our own upload path" would stop the next copy of bug 1.
4. Uploads are deleted only when no page or link references them (`BioUploads::deleteIfUnused`): good, but there is no sweep for files that lost their owner through a failed save. A scheduled `bio:prune-uploads` (dry run first) would keep the public disk honest.
5. Analytics are counted on the click redirect and the page view; a privacy-mode switch (above) needs one check in both places plus the editor.

## Suggested order

1. Linktree import (new page from a Linktree URL, with a test using a saved fixture page).
2. Privacy mode + a single Unlisted switch.
3. Account themes (save / apply appearance), without a marketplace.
4. Upload sweep command.
5. Custom domains, only after the hub has a plan for TLS and DNS.
