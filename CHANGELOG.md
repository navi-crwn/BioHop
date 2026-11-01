# Changelog

All notable changes to BioHop are listed here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added
- **New page wizard.** "New bio page" is now six short steps with a live phone: address, what the page is for, the platforms you are on (with your handles), your first links (name and icon filled in from the address), profile (picture, name, bio, content warning) and colours (wallpaper and buttons). One request builds the finished page and opens the editor. The plain form stays at `?classic=1`.
- **Collections.** A collection block groups everything placed under it, until the next collection or a divider, stacked or as a two-column grid; products under a collection are grouped on the Shop tab.
- **Archive and select mode.** A block can be archived: it leaves the page and the list but keeps its clicks, and can be restored or deleted from the archived list. In the editor, Select ticks several blocks to show, hide, archive or delete them together.
- The shop has its own address, `hel.ink/b/shop/{slug}`; a page that sells nothing shows no tab bar there and goes to its links. `shop` and `click` are reserved slugs.
- **Shop.** A new block kind, Product: picture, name, price with currency, a short description, where to buy (your shop, a marketplace listing or an affiliate link) and a "sold out" switch. Once a page has a product, the public page shows a Links · Shop tab bar and the products in a two-column grid; a product opens its address in a new tab and counts as a click, like a link. Without script `?tab=shop` opens the shop.
- The home page has a scroll scene for every feature, in the manner of the Amnesia and NoteHop sites: blocks that come to life, a pinned story from claiming a name to reading the numbers, themes that change as you scroll, analytics that draw themselves, a share map, the password and 18+ gates, CSS that types itself while the phone restyles, and a self-host terminal. GSAP and Lenis are bundled with the app (no CDN). With "reduce motion" on, every scene shows its finished state.
- The hero: pixels assemble into the BioHop logo, then the word BIO, then a phone, and the phone shows the real hel.ink/b/hel page live (an iframe of `?embed=1`), so whatever changes on that page shows there. The page stays, to be scrolled and tapped (links open in a new tab); the sequence plays again when the hero has left the screen and comes back.
- Deeper scenes, after the Amnesia site: the accent words of the hero break into particles as you scroll past; in "Blocks" a phone sticks beside the cards and every block flies into it as its card passes the middle of the screen; the themes stand in a ring around the phone that the scroll turns, the one in front dressing the phone; analytics gets an isometric 3D chart of clicks per day whose bars grow with the scroll.
- The sample page in the scenes (Helena Studio) carries a picture, a video and a player under its links, like a real page; the phone in the "Your style" scene keeps a phone's proportions.
- The HOP family section shows every other app as its own 3D mark (HOP ID, LinkHop, PixelHop, NoteHop), each one assembling from blocks and turning with a drag or the arrow keys; the flat mark stays without WebGL or with "reduce motion".
- `php artisan bio:official-page --apply` fills the official HEL.ink page from the shared manifest: a "Hop in" list with every public HOP app (mark, name), then one of every kind of block a page can hold (text, picture, a YouTube video and a Spotify player from `HOP_OFFICIAL_VIDEO` / `HOP_OFFICIAL_MUSIC`, a map, a contact card), "Start here" links (HOP ID, GitHub, status) and a FAQ. Run it again after the manifest changes; blocks added by hand on that page stay. The static copy in the hero shows the same apps.
- Only the official page (`HOP_OFFICIAL_BIO`, default `hel`), and only as `?embed=1`, may be framed by the HOP sites. An embedded view does not count as a visit, its links open in a new tab and it stays out of search. Every other page keeps `frame-ancestors 'none'`.

### Changed
- The dashboard top bar holds settings-type controls only (primary action, language, theme, and an account menu with your HOP ID, settings and sign out); the other HOP apps live in the sidebar, with their marks, and no longer also in the top bar. The sidebar's user card became the account menu.
- The editor is one page that scrolls normally, with the preview sticking beside it. It no longer sits in a fixed-height box that cut off its bottom and trapped the scroll wheel; on phones the preview is a full-screen sheet opened from a Preview button. The Save button and the tab bar no longer sit on top of each other.
- Editing saves by itself, as in Linktree: about a second after you stop, only what changed is sent (one field, one block, the new order). A status next to the button says "All changes saved", "Saving…" or "Not saved", Ctrl/Cmd+S saves at once, and autosave can be switched off. A problem is shown where you are working, with the field's own message, a jump to the right tab and a Try again button, instead of a short toast.
- The sidebar lists every public HOP app from the shared manifest, with the real marks: LinkHop, PixelHop and NoteHop (NoteHop was missing), and a single "Account (HOP ID)" entry.
- The editor's data comes in as JSON and its code is a cached file instead of a 3,000-line inline script; modals are created when opened. The page is about half the size, with half as many elements.
- Charts, the QR code library and the preview's Alpine are bundled with the app and loaded when needed, not from jsDelivr and unpkg (one of them blocked the page while it loaded).
- Fewer blurred layers (the app chrome only) and a fixed background that is composited rather than repainted, so scrolling is smooth.

### Fixed
- An uploaded background picture now saves. The upload worked, but the save then refused the file's own path as "a link type that is not allowed", so the page fell back to its theme; the check now applies only to outside addresses. A replaced background picture is deleted once nothing uses it.
- The phone in the hero is sized by the stage's height, so it no longer spills over the buttons above it and the running line below it on phones, before the 3D scene starts, or without WebGL; the "Live" note sits under the stage instead of on the phone. On phones the 3D scene fills the stage instead of a 240 px box.
- Counters never show a negative number for a frame (shared `hop-motion.js`).
- The preview in the editor scrolls again on every browser and on touch: the editor now sizes the frame to the whole page and scrolls it, instead of relying on a scroll box inside a frame inside a scrolling page.
- A link to hel.ink or a HOP app (note, pixel, link, bio ...) is kept as typed and no longer wrapped in a short link. `php artisan bio:unwrap-family-links` lists, and with `--apply` restores, blocks that were already wrapped.
- SVG icons, QR logos and thumbnails now upload. Laravel's `image` rule refuses SVG, so every SVG failed. An SVG is rebuilt from an allowlist (no script, handlers, external files or entities) before it is stored, and an SVG that cannot be made safe is refused with the reason.
- A block's custom icon was never saved for an existing block; edits are now detected by content, so no edit depends on a "mark as changed" call.
- Saving the SEO section failed (and could fail any save) when an older, longer SEO title was stored: the whole page was sent and validated every time. Only changed fields are sent now; the SEO title accepts 60 characters and the description 160.
- The dashboard cards' title no longer straddles the gradient strip.

### Added
- BioHop, the link-in-bio app of the HOP family, split out of LinkHop (plan: `docs/planning/PLAN-pemisahan-aplikasi.md`, Fase D).
- The `hop/bio` package (now owned by this repo): public pages at `hel.ink/b/{slug}`, `/b/{slug}/qr` and `/b/click/{id}`, the editor at `bio.hel.ink/bio`, themes, brands, uploads, analytics.
- Sign-in through HOP ID (`hop/auth`): no local login or registration; `/login` sends people to the hub and `/logout` ends the session here, then at the hub.
- Short links for blocks through LinkHop's API (`App\Bio\LinkHopClient`), with a per-member service token (scope `link:create`) created on first use and kept in `bio_linkhop_tokens`. When LinkHop cannot be reached the block keeps its original URL and the editor says so.
- URL and content safety through `hop/safety` (shared rules, shared domain blacklist); `abuse:scan` for bio pages and blocks; `privacy:prune` (bio clicks, 30 days); sitemap of indexable bio pages; CSP with the bio embed hosts.
- Admin pages for bio (list, inspect, monitor, export, unpublish, delete) for HOP admins.
- Shared database with the hub and LinkHop: migrations logged in `migrations_bio`; a baseline migration records the bio migrations LinkHop already ran, and every bio migration checks before it creates or adds anything.
- GitHub Actions: tests (Pest, sqlite) and Pint.
