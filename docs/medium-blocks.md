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


## Added later the same night

### Product details from an address (`ProductMeta`, `SafeFetch`)
In a product block, "Fill the details from this address" reads the shop page's Open Graph, `product:price:*` and JSON-LD tags and fills what is still empty (name, description, price, currency, picture). Limits: 2.5 seconds, 600 KB, two redirects each checked against private networks, html only. The picture is downloaded through the same checks and shrunk into our own storage, so the page never hot-links a shop. A site that fails is not asked again for ten minutes. Many marketplaces refuse bots; then the member sees a plain "type the details yourself". Rate limit: 20 a minute. `SafeFetch` is also what the feed block uses now.

### Webhook for form entries (`Webhooks`, table `bio_webhook_deliveries`)
Settings → Webhook: an https address, a signing secret (`whsec_…`, stored encrypted, shown to the owner only, can be replaced), a delivery log, "Send a test" and "Send again". Each request has `X-BioHop-Event`, `X-BioHop-Delivery`, `X-BioHop-Timestamp` and `X-BioHop-Signature: sha256=` plus the HMAC-SHA256 of `timestamp.body`. The first try runs after the visitor's response; a failed try is repeated after 1, 5 and 30 minutes by `bio:webhooks:retry` (scheduled every minute), four tries in all. Only https addresses that resolve to public networks are called, checked when saved and again before every send, with no redirects. The last 50 deliveries per page are kept.
**Needs the owner's attention:** a webhook moves visitors' details to a third party the page owner chose. The privacy policy wording about forms (see above) should cover it. The scheduler must be running on the VPS (it already runs `privacy:prune`).
