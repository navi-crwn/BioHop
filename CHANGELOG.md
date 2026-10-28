# Changelog

All notable changes to BioHop are listed here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added
- BioHop, the link-in-bio app of the HOP family, split out of LinkHop (plan: `docs/planning/PLAN-pemisahan-aplikasi.md`, Fase D).
- The `hop/bio` package (now owned by this repo): public pages at `hel.ink/b/{slug}`, `/b/{slug}/qr` and `/b/click/{id}`, the editor at `bio.hel.ink/bio`, themes, brands, uploads, analytics.
- Sign-in through HOP ID (`hop/auth`): no local login or registration; `/login` sends people to the hub and `/logout` ends the session here, then at the hub.
- Short links for blocks through LinkHop's API (`App\Bio\LinkHopClient`), with a per-member service token (scope `link:create`) created on first use and kept in `bio_linkhop_tokens`. When LinkHop cannot be reached the block keeps its original URL and the editor says so.
- URL and content safety through `hop/safety` (shared rules, shared domain blacklist); `abuse:scan` for bio pages and blocks; `privacy:prune` (bio clicks, 30 days); sitemap of indexable bio pages; CSP with the bio embed hosts.
- Admin pages for bio (list, inspect, monitor, export, unpublish, delete) for HOP admins.
- Shared database with the hub and LinkHop: migrations logged in `migrations_bio`; a baseline migration records the bio migrations LinkHop already ran, and every bio migration checks before it creates or adds anything.
- GitHub Actions: tests (Pest, sqlite) and Pint.
