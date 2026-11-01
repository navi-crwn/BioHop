# BioHop next to Linktree (2026)

Read on 2026-11-01 from saved copies of the Linktree admin (dashboard, Links, Header, Design) and its onboarding screens. What Linktree does that BioHop does not yet, and what to build, in order.

## What Linktree has today

- **Onboarding wizard** for a new page: goal (creator / business / personal) → platforms you are on (Instagram, WhatsApp, TikTok, YouTube, website, Spotify, …) → add links → profile (picture, display name, bio 160) → colours (wallpaper, buttons) with a live phone preview. The page exists and looks finished before the dashboard is ever shown.
- **Content tab = Links + Shop.** Links can be grouped in **collections**, archived, multi-selected. Shop holds **products** (picture, title, price, address, affiliate), also in collections, shown on a Shop tab of the public page.
- **Design tab**: Header (layouts Classic / Hero / Banner / Cutout, profile image shape, title as text or logo, title font and colour), Theme, Wallpaper, Text, Buttons, Colours, Stickers, Footer, Custom. Live preview beside it, "Preview as" a visitor.
- **Dashboard home**: greeting, visitor count for the week, setup checklist ("1 of 6 complete"), follower growth from connected socials.
- Enhance: Earn (tips, paid messaging), Media kit, Audience, Insights, Messaging, Tools (business cards, social planner, Instagram auto-reply, link shortener, post ideas), an AI assistant. Pro plan.

## What BioHop already has

Blocks of 18 kinds, 20+ themes, backgrounds (colour / gradient / uploaded picture), fonts, button shapes, 100+ social platforms, analytics without tracking, password and 18+ gates, QR, LinkHop short links, autosave with revisions, live preview, several pages per HOP ID, eight languages, abuse handling, admin in Imperium.

## Gaps, and what to build

| # | Build | Why | Size |
|---|---|---|---|
| 1 | **Shop**: a `product` block (picture, title, price + currency, address, optional "affiliate" note), **collections** (a header that groups the blocks under it into a grid or a row), and a **Shop tab** on the public page (Links · Shop) that appears once a page has a product. Editor: "Add product", drag into collections. | The owner's first ask; it is what makes a bio page a storefront. No payments inside BioHop: the product links out (own shop, Tokopedia, Shopee, Gumroad). | M |
| 2 | **Onboarding wizard** on "Create page": name → goal → platforms → links → profile → colours, with the live phone. Writes a complete page before the editor opens. | First impression; Linktree's biggest conversion trick. The pieces exist (claim form, socials, themes, preview). | M |
| 3 | **Header layouts** (Classic / Hero / Banner / Cutout) and title-as-logo. | Visual range of the page without new blocks. | S–M |
| 4 | **Collections, archive, select mode** for links. | Pages with 20+ links get unmanageable. Archive is a soft delete with its analytics kept. | S |
| 5 | **Design tab restructure** into Header · Theme · Wallpaper · Text · Buttons · Colours · Footer, with "Preview as visitor". | Same controls as today, found faster. | S |
| 6 | **Dashboard home**: setup checklist, visitors this week, last 7 days per page. | Cheap, and it drives people to finish their page. | S |
| 7 | **Linktree import** (from the Singlelink study). | Switchers. | S–M |
| — | Earn (tips, paid messages), Pro, media kit, social planner, auto-reply, AI assistant. | Not HOP's model: no payments, no paid tiers, no third-party data. Skip. | — |

## Rules that stay

Every string in nine languages, no CDN scripts, reduced motion complete, one PR per repo, tests first (Pest), no AI traces. A new block kind touches: `BioLink::TYPE_NAMES`, the add-block modal, the editor form, the preview, `show.blade.php`, validation in `BioLinkController`, and a test.
