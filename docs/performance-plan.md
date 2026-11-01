# Making the public page light and the clicks instant

**Status 2026-11-01 night: points 1, 3, 5, 6, 7, 8, 14 and the click fix are built on `feat/page-settings`; 2 is capped instead of converted; 10 and 11 are not built; 4 and 9 are rejected. Results are in the last section.**

Written 2026-11-01 night from the owner's performance list (15 points and a benchmark table). Every claim below was measured or read in code on that day, not assumed. Local numbers come from `php artisan serve` on the Mac with a SQLite database, no gzip, no opcache, the sample page `/b/helena` at 390 px in Chromium. They show where the weight is; they are not production numbers. Re-measure on the VPS after each step (the VPS has 4 cores, 8 GB, PHP with GD + WebP + Imagick, and ffmpeg).

## What we measured

| Metric | Now (local) | Target | Verdict |
|---|---|---|---|
| Server time to first byte, page warm | 9 ms (first browser hit 170 ms) | < 50 ms | fine here; production database may add 20–60 ms |
| Click on a link → redirect | **330 ms → 8 ms** after the fix of 2026-11-01 (cold user-agent parse, now cached) | 0 felt | **fixed** (commit `5d4b186`) |
| LCP | 520 ms | < 1.2 s | fine |
| CLS | 0.0004 | 0.00 | fine (the avatar has `width`/`height`); other images lack them |
| Page weight, first view | ~470 KB uncompressed: HTML 72 KB, CSS 182 KB, JS 108 KB + Alpine 55 KB, avatar 67 KB | < 150 KB | gzip/brotli will cut text by ~70%; the avatar and the JS bundle are the real weight |
| Third-party requests | **Google Fonts CSS + font files on every public visit** | none | to fix (point 8) |
| `backdrop-filter` elements | 2, blur 4–10 px | ≤ 12 px | fine |
| Iframes at load | maps only; video/music embeds render real iframes | facade | to build (point 5) |
| Cookies | every public visit sets `XSRF-TOKEN` and a session cookie, `Cache-Control: private` | none for anonymous reading | blocks any shared/page cache until changed |

## The 15 points, one by one

**1. Compress and resize uploads (WebP, avatar ≤ 250 px, thumbnails ≤ 120 px, background ≤ 1080 px)** — *not there*. Uploads are validated and stored as they came (limits 2–5 MB). Build with GD (present locally and on the VPS): on upload, resize to the target, write WebP (AVIF later), keep the original out of the public disk. Backfill command for existing files. **High value, medium work.**

**2. GIF → silent MP4/WebM** — *not there*. ffmpeg exists on the VPS, so it is possible, but a conversion takes CPU seconds on a small server. Do it in a queued job (queue driver is `database`), with a size cap on the GIF, and render `<video autoplay loop muted playsinline>`. Until then: refuse GIFs above ~1 MB. **Medium.**

**3. BlurHash / LQIP** — *not there*. Cheaper and almost as good: store the image's average colour (7 bytes) at upload and use it as the box background, plus `width`/`height` on every image (fixes CLS). No decoder JavaScript. **Small, after 1.**

**4. Direct-to-cloud (presigned) uploads** — *not applicable*: there is no object storage, one VPS, files go to the public disk. Skip.

**5. Iframe facade for video and music** — *not there*. YouTube and Spotify embeds render a real iframe on load (tens of KB of third-party script each). Show a thumbnail (YouTube's still image) or a plain card with a play button, and create the iframe on the first tap. **High value, small work.**

**6. `content-visibility: auto` on shop cards and long lists** — *not there*, one CSS line per card plus `contain-intrinsic-size`. Infinite scroll is unnecessary below a few hundred products. **Small.**

**7. Limit glassmorphism and add a lite mode** — blur is already ≤ 10 px on the public page and reduced motion is respected. Add the `Save-Data` header and `prefers-reduced-data` to switch off background animation and video. **Small.**

**8. Self-hosted fonts** — *not there on the public page*: it asks Google Fonts per visit, which sends every visitor's address to Google and flashes text. The hub already serves fonts itself (`<x-hop::fonts />` on the password page). Ship the 11 fonts the editor offers as Latin-subset `.woff2` (OFL licence) with `font-display: swap`, load only the one the page uses, preload it. **High value (privacy + speed), medium work.**

**9. `sendBeacon` / `ping` for click counting** — *not needed*. A link click is already a server redirect whose counting runs after the response is sent; with the user-agent fix it takes ~8 ms. A direct `href` plus `ping` would lose bot filtering and per-visitor dedupe and exposes destinations to scrapers; `ping` is also disabled in Firefox. Revisit only if the redirect hop shows up on the VPS.

**10. Pre-compiled snapshot / zero-SQL public page** — *partly*. The page needs 3 queries (page, blocks, user); the official page is already cached. A snapshot cache needs two things first: the public page must stop setting a session for anonymous readers (the XSRF cookie exists for forms and polls; load a token lazily only on pages that have a form or poll), and the cache must be invalidated on every save (the editor already has one choke point per write, the new versions hook is one). **Medium; do after 1, 5, 8 and measure on the VPS first.**

**11. Stale-while-revalidate and a fallback when the database is down** — *not there*. The practical way on one VPS is nginx `fastcgi_cache` with `use_stale error timeout updating` on `/b/{slug}` for requests without a session cookie. It depends on point 10 (no cookie on anonymous reads). The nginx change is the owner's. **Later.**

**12. Circuit breaker for scrapers** — *done for the feed block* (3 s timeout, 2 redirects, failures cached 10 min, last good copy kept 7 days). The product-metadata fetch is not built yet; build it with a 2.5 s limit and a visible "type it yourself" fallback.

**13. Debounced editor and isolated state** — *there*: changes are checked after 150 ms, autosave waits 1.4 s, the preview is queued and only the changed parts of the data are posted to it. No further work unless a real page with 50+ blocks feels slow; test that case on the Mac first.

**14. Page health meter** — *not there*. A small pill beside the live preview: counts embeds, images over a size, blocks, third-party scripts; turns amber with one plain sentence ("6 videos: they load when tapped, but the page is long"). **Small, after 5.**

**15. Layered rate limits** — *there*: link clicks (300 per minute flood guard, at most 20 counted per IP per block per 10 minutes), forms 5 per IP per 10 minutes plus a hidden field and Turnstile, polls 20 per 10 minutes, link PINs 10 per 10 minutes, page passwords 10 per 10 minutes, uploads behind login. Redis is not needed at this size; the cache is the database table.

## Order I would take

1. Self-hosted fonts (8): removes the only third-party call.
2. Image pipeline (1) with `width`/`height` and average colour (3): the biggest bytes.
3. Iframe facade (5) and `content-visibility` (6).
4. Lite mode (7) and the page health pill (14).
5. No session for anonymous reads → page cache (10) → nginx stale (11), measured on the VPS.
6. GIF conversion (2) last, as a queued job.

## Not doing
Presigned uploads (4), `sendBeacon` (9), ClickHouse and table partitioning, a separate "control plane / data plane" service, BlurHash with a JavaScript decoder.


## What was built, and what it measured (same page, same method, after the work)

| Metric | Before | After |
|---|---|---|
| Link click → redirect | 330 ms | 8 ms (a user agent seen for the first time still pays 330 ms once) |
| Requests to other sites when the page opens | Google Fonts (CSS + font files) | **0** |
| Profile picture of the sample page | 67 KB PNG, 600 px | 6.6 KB WebP, with `width`/`height` and a placeholder colour |
| Sample cover picture | 193 KB PNG | 10 KB WebP |
| Layout shift (CLS) | 0.0004 | 0.0000 |
| Videos, music, maps on open | real iframes (YouTube alone pulls tens of requests and ad scripts) | a placeholder card; **0 external requests** until tapped |
| First view on the wire, compressed | about 160 KB: HTML 16, CSS 30, JS 39, Alpine 20, font 48 (woff2), picture 7 | target < 150 KB: within about 10 KB |

Local numbers (Mac, `php artisan serve`, SQLite); the shape is what matters. Measure again on the VPS.

### 1 · Pictures (done): `ImageOptimizer`, `ImageMeta`, `bio:optimize-images`
Every upload of an avatar, thumbnail, icon, picture or background is scaled (longest edge 640 / 240 / 160 / 1600 px, never enlarged), rotated by its camera orientation, and stored as WebP (PNG for favicons, at most 128 px). Upload limits were raised (avatar 8 MB, picture 10 MB, thumbnail/icon 4 MB) because the file is shrunk anyway; a picture that would not fit the PHP memory limit is refused with a clear message. The public page reads each stored picture's size and average colour once (cached) and prints `width`, `height` and a background colour while it loads. **After merging, run once on the VPS:** `php artisan bio:optimize-images` (a report) and then `php artisan bio:optimize-images --apply`; it converts the old PNG/JPEG files, updates every page and block that points to them (including carousel pictures) and deletes the old files. Favicons are skipped. Note: `bio:official-page --apply` still writes the official avatar as a PNG; run the optimizer after it.

### 2 · GIF (capped, not converted)
An animated GIF cannot be resized with GD. Instead: animated GIFs over 1 MB are refused with an explanation, lighter ones are kept as they are, and the page speed pill in the editor warns about GIFs. Converting to MP4/WebM with ffmpeg (present on the VPS) means a queued job and `<video>` markup in every place a picture can appear (avatar, thumbnail, icon, background); not worth it until members actually upload GIFs.

### 5 · Player facades (done)
Video (YouTube, Vimeo), TikTok, Spotify, SoundCloud, Apple Music and map blocks render a card with a play button and the line "Loads from YouTube when you tap". One small script swaps in the real iframe on tap; a modified click or no JavaScript keeps a plain link to the original. The facade adds nothing from other sites on load and no thumbnail request.

### 6 · `content-visibility` (done)
Shop cards, players, feeds, polls and forms are not painted until they come close to the screen.

### 7 · Lite mode (done)
A visitor who sends `Save-Data`, has `prefers-reduced-data`, reports 2 GB of memory or fewer or 2 cores or fewer, or opens `?lite=1`, gets no background animation, no entrance effects and no blur (`?lite=0` turns it off). The server also adds the class when the `Save-Data` header is present.

### 8 · Fonts (done): `scripts/build-bio-fonts.py`, `public/fonts/bio/`
The 20 fonts the editor offers are committed as Latin-subset woff2 (26 files, 834 KB in total; a page loads one font, about 15–50 KB). The page inlines the small `@font-face` block and preloads the regular weight. The editor preview uses one combined stylesheet. Limit: pages written in other scripts (Cyrillic, Chinese, Arabic) fall back to the system font for those letters.

### 14 · Page speed pill (done)
A pill beside the live preview (⚡ 94/100) opens a short list of plain-language tips: many players, many pictures, long pages, a background animation, tracking scripts, GIFs, older pictures not yet shrunk. It is an estimate from what is on the page, not a measurement, and says so.

### Still open, in this order
1. **A slim public bundle.** The public page loads the whole app's stylesheet (183 KB raw, 30 KB gzip) and script (108 KB + Alpine 55 KB raw, about 58 KB gzip together) because it shares the `app` entry. A separate Vite entry for public pages (Alpine plus the few components it uses; Tailwind scanned from the public views only) would roughly halve both. Medium work, safe, measurable.
2. **No session for anonymous readers, then a page cache** (points 10 and 11). Needs the XSRF token loaded lazily only where a form or poll exists. Measure on the VPS first; the server time is already 9–170 ms.
3. **GIF conversion**, only if needed.

### After merging (owner)
`php artisan migrate`, then `php artisan bio:optimize-images --apply`; nginx should serve `/fonts/bio/` and `/storage/` with long cache headers (the file names change when a font or picture changes); gzip or brotli for text if not already on. No new environment variables.
