# Changelog

All notable changes to BioHop are listed here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Changed
- The editor is one page that scrolls normally, with the preview sticking beside it. It no longer sits in a fixed-height box that cut off its bottom and trapped the scroll wheel; on phones the preview is a full-screen sheet opened from a Preview button. The Save button and the tab bar no longer sit on top of each other.
- Editing saves by itself, as in Linktree: about a second after you stop, only what changed is sent (one field, one block, the new order). A status next to the button says "All changes saved", "Saving…" or "Not saved", Ctrl/Cmd+S saves at once, and autosave can be switched off. A problem is shown where you are working, with the field's own message, a jump to the right tab and a Try again button, instead of a short toast.
- The sidebar lists every public HOP app from the shared manifest, with the real marks: LinkHop, PixelHop and NoteHop (NoteHop was missing), and a single "Account (HOP ID)" entry.
- The editor's data comes in as JSON and its code is a cached file instead of a 3,000-line inline script; modals are created when opened. The page is about half the size, with half as many elements.
- Charts, the QR code library and the preview's Alpine are bundled with the app and loaded when needed, not from jsDelivr and unpkg (one of them blocked the page while it loaded).
- Fewer blurred layers (the app chrome only) and a fixed background that is composited rather than repainted, so scrolling is smooth.

### Fixed
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
