# 100 link-in-bio features, mapped to BioHop

The owner pasted a list of 100 features (two parts, 1–50 and 51–100) on 2026-11-01. This file says, per feature, where BioHop stands and whether it fits HOP's rules: free, no payments inside, no paid tiers, no third-party data, no CDN scripts, every string translated.

Legend: **have** = in BioHop today (feat/home-p8); **partly** = a piece exists; **build** = fits HOP, worth doing; **later** = fits, but needs infrastructure first; **no** = against HOP's model or not worth the risk.

## 1. Links, routing, content (1–13)

| # | Feature | Status | Note |
|---|---|---|---|
| 1 | Unlimited links, drag-and-drop | have | Sortable with touch fallback; collections nest in the list since today. |
| 2 | Deep linking / in-app browser bypass | build | Small: for known hosts (Shopee, Tokopedia, TikTok, Instagram, YouTube) emit the app scheme or intent URL on the click route when the user agent is an in-app browser. Needs a per-host table and a fallback. |
| 3 | Geo / device / time routing | build (M) | The click route already knows country, device and time. A "rules" setting per link: list of `[condition → url]`, first match wins. |
| 4 | Fallback links when the main one is down or blocked | later | Needs a background checker (queue + scheduler) and a notion of "blocked by ISP", which is hard to measure from one server. Do the checker first (#55), then this. |
| 5 | WhatsApp rotator | have | `whatsapp` block: up to 10 numbers, in turn (one cache counter per block) or by weight, pre-filled text; the number is picked on the click route. |
| 6 | Link scheduling | have | `publish_at` / `expire_at` on every block kind (Schedule section in the block form); hidden on the page and the preview outside the window, the click address answers 404, the list shows Scheduled / Expired chips. |
| 7 | Spotlight / priority link animation | have | Per-link "Attention animation" (pulse, bounce, shake, wiggle, glow, heartbeat) and entrance animations already existed in the link form. |
| 8 | Built-in shortener and cloaking | have | LinkHop short links from the editor (`/bio/shorten`), via the member's token. |
| 9 | Media embeds | have | YouTube, Spotify, SoundCloud, video, music blocks. TikTok and Twitch embeds: build, small. |
| 10 | Rich content blocks | have / partly | Text, divider, spacer, FAQ, image, map, countdown, HTML, code. Missing: image carousel, PDF viewer (build, S each). |
| 11 | Password-protected links | partly | The page can have a password; a per-link PIN is small (gate on the click route). |
| 12 | Age / sensitive gate | have | Page-level 18+ and sensitive warnings. Per-link gate: small. |
| 13 | Countdown timer | have | Countdown block. |

## 2. Design, branding, UI (14–23)

| # | Feature | Status | Note |
|---|---|---|---|
| 14 | Custom domain with auto-SSL | later | Needs DNS verification, TLS at the edge and nginx mapping; the hub decides (see singlelink-comparison.md). |
| 15 | Niche templates | partly | 20+ themes and the wizard's goal → theme. A "templates" step with blocks pre-filled per niche is small once the wizard exists. |
| 16 | Grid / bento layouts | partly | Collections can be a 2-column grid; the shop is a grid. A page-wide bento layout: build (M), needs block size settings. |
| 17 | Advanced backgrounds | have / partly | Colour, gradient, picture, animations. Patterns: small. Video background: no (weight on mobile, autoplay rules, CSP). |
| 18 | Detailed button styles | have | Shape, shadow, hover, colours, per-block colours. Glassmorphism: small (one more shape/shadow option). |
| 19 | Thumbnails and icon library | have / partly | Uploaded thumbnails and custom icons, 100+ brand icons. A vector icon library (Lucide) bundled: build (S), no CDN. |
| 20 | Custom CSS / HTML | have | `custom_css` per page, HTML block (sanitised). Page-level HTML injection: no (XSS surface). |
| 21 | White-labeling | have | "Powered by" can be switched off, free for everyone (Design → Footer). |
| 22 | Verified badge | have | Badge next to the title (verified, star, crown, fire); who gets "verified" is an admin matter in Imperium. |
| 23 | Auto dark/light | partly | The dashboard follows the system; public pages use the chosen theme. A "follow visitor's system" theme pair: build (S). |

## 3. Monetisation, commerce, leads (24–37)

| # | Feature | Status | Note |
|---|---|---|---|
| 24 | Affiliate showcase | have | Shop tab, product blocks with picture, price, was-price, sold out, variants, stock, collections. Search box: small. |
| 25 | Tip jar with QRIS/GoPay/Stripe | no | Payments inside HOP are out. A "tip" block that links to the creator's own QRIS image or payment page is fine (it is a link + picture, already possible). |
| 26 | Digital product sales with delivery | no | Payments and file delivery. Link out to Gumroad/Lynk/Mayar instead (already possible). |
| 27 | Paywalled links | no | Payments. |
| 28 | Booking calendar | partly | Calendly / Cal.com brand links exist; an embedded booking block (iframe of cal.com) is small. |
| 29 | Rate card block | build (S) | A list of services with prices and a "book" address: close to the product block; could be a product collection with a "services" layout. |
| 30 | Lead capture form (email/WhatsApp) | build (M) | A form block storing entries on the page owner's account, CSV export. Keep the data in HOP, no Sheets/Mailchimp push by default (webhook, #49, covers it). Privacy text and retention needed. |
| 31 | Subscribe-to-unlock | build (S) after #30 | Gate a link behind the form. "Follow to unlock" cannot be verified without platform APIs: no. |
| 32 | vCard download | have | Contact card block with .vcf. |
| 33 | Dynamic QR with colours and logo | have | QR settings per page with logo. |
| 34 | Social auto-feed | no | Needs Instagram/TikTok/YouTube APIs or scraping; third-party data and tokens. RSS (#56) is the honest version. |
| 35 | Anonymous message box | build (M) | A form block variant; moderation and abuse handling needed (reports, bans exist). |
| 36 | Web3 wallet / NFT | no | |
| 37 | Internal affiliate programme | no | No paid tiers to sell. |

## 4. Analytics, safety, infrastructure (38–50)

| # | Feature | Status | Note |
|---|---|---|---|
| 38 | Real-time analytics (views, clicks, CTR) | have / partly | Clicks per day, per link, countries, devices, referrers, CTR; views are only a counter. Build: a daily views table (privacy: keyed hash, 30 days) so the dashboard can show visitors per week. |
| 39 | Third-party pixels | have | GA, Meta, TikTok pixel ids on the page (consent is the owner's responsibility; see #98). GTM: small. |
| 40 | Automatic UTM | have | Settings → "Add UTM to outgoing links": the click route appends `utm_source=biohop&utm_medium=bio&utm_campaign=<slug>` to http(s) links that carry no UTM of their own. |
| 41 | Referrer and geography | have | |
| 42 | A/B testing | build (M) | Two titles/colours per block, visitor bucket by keyed hash, click counts per variant. |
| 43 | Multiple pages per account | have | Up to the page limit; dashboard home lists them. |
| 44 | Team roles | later | HOP ID would need shared workspaces; a BioHop-only "editor" invitation is possible but belongs in the hub's account model. |
| 45 | SEO and Open Graph | have | SEO title, description, noindex; OG image is the avatar. A custom OG image upload: small. |
| 46 | Phishing/malware/porn scanner | have | hop/safety on every address (blacklist, patterns); periodic re-check of stored links: build (S) with the checker from #55. |
| 47 | Anti-bot and click fraud | have | Rate limits on click and shorten, Turnstile on public forms, keyed visitor hash. |
| 48 | Edge caching, fast SSR | partly | Pages are server-rendered Blade with no CDN scripts; the official page is cached. Full-page cache per slug (invalidate on save): build (S). |
| 49 | Webhooks | build (M) | Per page: an https URL and a secret; events for form entries (#30) and daily stats. Signed payloads, retries, no third-party connectors. |
| 50 | 1-click migration from Linktree | have | `/bio/import`. Heylink and others: add parsers when someone asks. |

## 5. AI and automation (51–62)

| # | Feature | Status | Note |
|---|---|---|---|
| 51 | AI page generator | no | HOP has no AI assistant (decided earlier: not HOP's model, third-party data). The wizard covers the "from a few answers" case. |
| 52 | Auto-fetch product metadata from a URL | build (M) | Fetch the shop page's Open Graph tags (title, image, price if present) when a product address is pasted. Same fetch guard as the Linktree import (allowed hosts, size and time limits). Shopee/Tokopedia block scrapers; expect partial results. |
| 53 | AI copywriting | no | |
| 54 | AI chatbot on the page | no | |
| 55 | Broken link / out-of-stock scanner | build (M) | Scheduled HEAD/GET of each link, flag 404/timeout in the editor, e-mail digest. Stock detection: no (per-shop scraping). |
| 56 | RSS / blog / podcast auto-sync | build (M) | An `rss` block: feed URL, shows the latest n items, cached hourly. Spotify podcast via its RSS. |
| 57 | AI auto-sort by CTR | partly, no AI | "Sort by clicks" button in the editor is small and honest; automatic reordering surprises visitors: no. |
| 58 | Live auto-detect | no | Needs platform APIs. |
| 59 | Auto-translate the page | no | Machine translation needs a third-party service; the editor already lets the owner write in any language, and the chrome is in 8 (soon 9) languages. |
| 60 | Background remover and optimiser | partly | Uploads are resized; WebP output: small. Background removal: no (model or service). |
| 61 | Smart app store redirect | build (S) | A `app` block with App Store, Play and web addresses; the click route picks by device. |
| 62 | Voice intro | build (S) | An audio block with an uploaded clip (size limit, no autoplay). |

## 6. Interaction, community, gamification (63–75)

| # | Feature | Status | Note |
|---|---|---|---|
| 63 | Spin-the-wheel | no | A lead-capture lure; skip. |
| 64 | Polls | build (M) | A poll block, one vote per keyed visitor hash, results on the page. |
| 65 | Quiz / product recommender | later | After polls; logic trees are a bigger editor. |
| 66 | Scratch card | no | |
| 67 | Live status badge | build (S) | A page setting: a short status chip under the name ("Open for commissions"), optional expiry. |
| 68 | Guestbook / wall of love | build (M) | Public entries with owner approval; abuse reports exist. |
| 69 | Event RSVP with QR and .ics | partly | Countdown and vCard exist; RSVP = form block (#30) + .ics download (small). Tickets by e-mail: later. |
| 70 | Giveaway manager | no | Verification of "followed", "shared" is not possible honestly. |
| 71 | Floating action button | build (S) | Page setting: one sticky button (WhatsApp, shop, custom), reduced-motion safe. |
| 72 | Exit-intent / timed pop-up | no | Hostile to visitors. |
| 73 | Social-proof toasts | no | Fabricated urgency. |
| 74 | Own audio player | build (S) | Same as #62 with a track list. |
| 75 | Shareable profile card (story image) | build (S) | Server-rendered PNG (avatar, name, QR) via the existing QR code; one route. |

## 7. Advanced monetisation (76–88)

| # | Feature | Status | Note |
|---|---|---|---|
| 76 | Live media kit from platform APIs | no | Third-party tokens. A static media-kit block (numbers typed by the owner) is a text/product block already. |
| 77–78 | Sponsored slots, paid DMs | no | Payments. |
| 79 | Micro-course hosting | no | Video hosting and payments. |
| 80 | Paid memberships | no | |
| 81 | Multi-currency pricing | partly | A product has one currency; showing a second currency by visitor country: small, but exchange rates would need a source. |
| 82 | Discount codes | partly | A "coupon" block (code to copy + address) is small and honest; quota needs the shop to be ours: no. |
| 83 | Order bump / upsell | no | |
| 84 | Affiliate auto-monetiser | no | Rewriting links to add the platform's own affiliate ids is exactly what HOP does not do. Keeping the owner's affiliate parameters as typed: already the case. |
| 85 | Goal progress bar | build (S) | A block with a target, a current value typed by the owner, and an address. No payments involved. |
| 86–88 | Invoices, revenue split, cart | no | Payments. |

## 8. Infrastructure, developer tools, enterprise (89–100)

| # | Feature | Status | Note |
|---|---|---|---|
| 89 | PWA per profile | build (S) | A manifest per slug and a tiny service worker that caches the page; icon = avatar. |
| 90 | NFC tag parameters | build (S) | `?src=nfc` and `?src=qr` on the public page, counted as referrer sources (the QR route can add it itself). |
| 91 | Click and scroll heatmap | no | Needs per-pixel tracking of visitors. |
| 92 | Public API | later | The hub's token model exists (LinkHop API); a BioHop read/write API is M and should follow the hub's API conventions. |
| 93 | Telegram/WhatsApp bot controller | later | After the API. |
| 94 | Domain block monitor | later | With custom domains (#14). |
| 95 | Audit log | partly | Page revisions exist (autosave with revisions); a per-change log with who/when: small once teams (#44) exist. |
| 96 | Version history and rollback | partly | Revisions exist for the page; a one-click restore of blocks + design snapshot: build (S). |
| 97 | White-label agency portal | no | Not HOP's model. |
| 98 | Cookie consent banner | build (S) | Only shown when the owner enabled a third-party pixel; HOP's own analytics need no cookies. |
| 99 | Weekly digest by e-mail | build (S) | The dashboard-home numbers, mailed weekly (opt-in). WhatsApp delivery: no. |
| 100 | Accessibility mode | have / partly | Reduced motion, aria on tabs/lists, contrast in themes; an audit pass with axe on the public page and the editor is still due. |

## What this suggests for the next rounds

Small and clearly in scope (S, one PR each): live status chip (67), floating button (71), app-store redirect (61), goal progress bar (85), PWA manifest (89), `?src=` sources (90), weekly digest (99), consent banner (98), icon library (19), custom OG image (45), full-page cache (48), one-click restore (96), share card PNG (75).

Medium (M): geo/device/time routing (3), lead-capture form with CSV and webhooks (30, 49), broken-link checker (55) and then fallback links (4), RSS block (56), product metadata fetch (52), polls (64), guestbook (68), A/B titles (42), daily views table (38).

Out, on HOP's rules: anything that takes payments (25–27, 77–80, 83, 86–88), paid tiers and affiliate programmes (37, 97), AI features (51, 53, 54), third-party platform data (34, 58, 76), dark patterns (63, 66, 70, 72, 73), heatmaps (91), affiliate rewriting (84).

The two questions at the end of the pasted list (target market for an MVP; SaaS multi-tenant from day one) were written by whoever produced the list, not answered here. HOP's answer so far: one free product for everyone with a HOP ID, no tenants, no plans; the owner decides the market.
