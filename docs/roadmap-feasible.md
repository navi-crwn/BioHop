# What BioHop can still take on

Written 2026-11-01 from two pasted lists (the 100 features, mapped in `feature-map-100.md`, and an "enterprise settings / infrastructure / dashboard" note). The owner's two exclusions apply: **no team or workspace features** (roles, audit logs per member, SSO, shared workspaces) and **no custom domains** (DNS, SSL, domain monitors): the server is a single VPS and the hub owns accounts.

HOP's standing rules also apply: free, no payments inside, no paid tiers, no third-party platform data, no AI features, no CDN scripts, every string translated.

## Already there (nothing to do)

Page-level: SEO title/description, noindex, pixels (GA, Meta, TikTok), age gate and sensitive warning, page password, hide "Powered by", custom CSS, QR with logo, vCard, countdown, embeds, FAQ, shop with variants and stock, collections, archive, Linktree import, wizard, dashboard checklist, scheduling, UTM, WhatsApp rotator, attention animations, bulk actions (show/hide/archive/delete), autosave with revisions, live preview (an iframe fed by postMessage, no reload: the "zero-latency" preview already behaves this way), 2FA at the hub, link safety on every address, click rate limits and keyed visitor hashes, 30-day click logs.

## Feasible, in the order I would do them

### Page settings (small) — done 2026-11-01 evening on `feat/page-settings` (items 1–8; GTM shares the existing consent banner)
1. **Custom OG image** upload (falls back to the avatar) and **favicon per page**.
2. **Google Tag Manager id** next to the pixels.
3. **Cookie consent banner** shown only when a third-party pixel is on; HOP's own counting needs no cookie.
4. **Fallback redirect**: where a visitor goes when the page is unpublished or password-locked and the owner wants a different landing (a URL field; default is the current "not found" page).
5. **Disclaimer / footer line**: a short text under the blocks with an optional link.
6. **Per-link PIN** (the page-level password exists): a code on one block, checked on the click route.
7. **Unlisted switch** that sets noindex + hidden from directory together (Singlelink study).
8. **Privacy mode**: stop counting views and clicks for a page.

### Blocks and links (small to medium)
9. **Status chip** under the name ("Open for commissions"), optional expiry.
10. **Floating action button** (sticky WhatsApp / shop / custom), reduced-motion safe.
11. **App-store redirect** block (App Store / Play / web by device).
12. **Goal progress bar** block (target, current value typed by the owner, address).
13. **Audio clip** block (uploaded mp3, size limit, no autoplay) and a small **track list**.
14. **Image carousel** and **PDF viewer** blocks (menu, catalogue).
15. **Coupon** block: code to copy + address, no quota.
16. **TikTok and Twitch embeds** next to YouTube/Spotify.
17. **Icon library** bundled (Lucide), no CDN.
18. **Geo / device / time routing** on a link: `[condition → url]` rules, first match wins; the click route already knows country, device and time.
19. **RSS block**: latest n items of a feed, cached hourly (blog, podcast).
20. **Product metadata from a URL**: Open Graph title/image/price when a product address is pasted; same fetch guard as the Linktree import; Shopee/Tokopedia will often refuse, so it stays best-effort.
21. **Polls** (one vote per keyed visitor hash) and **guestbook** with owner approval.
22. **Lead-capture form** block (name, e-mail, WhatsApp) with CSV export, stored in HOP, retention setting, Turnstile; then **subscribe-to-unlock** and **RSVP with .ics**.
23. **Webhooks** per page (https URL + secret, signed payloads, retries, a retry button) for form entries and daily stats.
24. **A/B titles** on a link (two titles, bucket by keyed hash, clicks per variant).

### Dashboard and editor (small to medium)
25. **Bento block picker**: the add-block modal grouped as Essentials · Media · Shop · Smart (same blocks, better found).
26. **Advanced-settings row on each card**: schedule, routing, animation, PIN, UTM as small icons that open an accordion under the card instead of the modal.
27. **Command palette** (Cmd/Ctrl+K): add block, copy link, open a tab, switch theme; bundled, no library.
28. **Draft & publish** mode per page: edit a draft copy, secret preview link, "Publish changes" or publish at a time. Medium: needs a draft snapshot of page + blocks (revisions exist, this builds on them).
29. **Bulk "apply UTM" / "move to collection"** in select mode.
30. **One-click restore** of a revision (blocks + design snapshot).
31. **Sort by clicks** button in the editor (manual, no auto-reorder).
32. **Page switcher** in the sidebar (dropdown of the account's pages).
33. **Weekly digest** e-mail (opt-in): the dashboard-home numbers.
34. **Share card PNG** (avatar, name, QR) for stories.
35. **PWA manifest** per page; **`?src=qr|nfc`** counted as sources.

### Infrastructure that fits one VPS (medium)
36. **Full-page cache per slug** (file or Redis if present), invalidated on save: the public page then needs no DB query for most visits. The official page already does this.
37. **Click ingestion through the queue**: the click route already defers the insert after the redirect; moving it to a queued job with batch inserts is the next step if traffic grows. Views: a daily views table (keyed hash, 30 days) so the dashboard can show visitors.
38. **Broken-link checker**: scheduled HEAD/GET per link, flag in the editor, e-mail digest; then **fallback link** when the main one fails. Re-check stored links against hop/safety periodically (the "cloaking scanner" without a third-party reputation API).
39. **Analytics table pruning and indexes** (monthly partitions are overkill for one VPS; the 30-day prune exists).
40. **Separate public and dashboard routes in nginx** (same app, different cache rules), so a dashboard deploy does not touch cached public pages. The "control plane / data plane" split as two services is not worth it on one server.

## Not doing, and why
- Teams, roles, audit logs per member, SSO, workspaces: excluded by the owner; the hub owns accounts.
- Custom domains, DNS/SSL provisioning, domain block monitors, backup domains: excluded by the owner.
- Billing, ledger, payouts, invoices, tip jar, digital products, paywalls, memberships, cart: HOP takes no money.
- Conversion API tokens (server-side pixels), social auto-feeds, live media kits: third-party tokens and data.
- AI generators, chatbots, copywriting, auto-translate.
- Page speed score in the preview, heatmaps, ClickHouse: measurement theatre for this size.
- Spin wheels, scratch cards, exit pop-ups, fake social-proof toasts.
