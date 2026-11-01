# Medium blocks: how they work and what to watch

Added 2026-11-01 night on `feat/page-settings` (BioHop). Each has its own commit and tests.

## Routing rules on a link (`settings.rules`, up to 10)
Per rule: countries (ISO codes, comma separated), device (phone / tablet / computer), system (iPhone-iPad / Android), hours (from–to in the owner's time zone, may wrap midnight) and an address. The first rule whose conditions all hold decides; everyone else gets the link's own address. The country lookup runs only when a rule has a country, so most clicks cost nothing extra. Addresses are safety-checked when saved and again through `SafeContent::url` on use. UTM (if on) is added after routing.

## Sign-up form block (`form`, table `bio_leads`)
Fields on or off: name, e-mail, WhatsApp number. The visitor must tick a consent line the owner can word. Honeypot field, Turnstile when configured, 5 tries per IP per 10 minutes, at most 5000 entries per form. Stored: the asked fields and a keyed hash of the sender's address. The owner downloads a CSV (formula-looking cells are prefixed with an apostrophe) or deletes all entries. `privacy:prune` deletes entries after the days the owner chose (1–365, default 90).
**Needs the owner's attention:** the privacy policy should say that page owners can collect details through forms and that HOP stores them for the retention period on the owner's behalf. I did not edit the policy text.

## Feed block (`rss`)
RSS 2.0 and Atom. `FeedReader` checks the address with `Hop\Safety\UrlGuard` (no private or reserved networks), follows at most two redirects and checks each, caps the body at 1 MB and times out at 3 s. Cached one hour; a failed fetch keeps the last good copy (7 days) and retries after ten minutes. The feed is fetched when the block is saved so the first visitor does not wait. Item titles are stripped of markup and only http(s) links are kept.

## Poll block (`poll`, table `bio_poll_votes`)
2–6 options; one vote per device (keyed hash of address and browser, unique per poll); results with percentages after voting. Not an election: two people behind one address with the same browser count once, and someone who changes browser can vote again. The owner sees counts in the block form and can reset them. Votes are not shown as personal data and need no cookie.

## Not done from this group
Guestbook with approval, subscribe-to-unlock and RSVP/.ics (both build on the form), A/B titles, webhooks, metadata from a product URL.
