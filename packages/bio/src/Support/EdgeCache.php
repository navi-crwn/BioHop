<?php

namespace Hop\Bio\Support;

use Hop\Bio\Jobs\PurgeEdgeCache;
use Hop\Bio\Models\BioPage;

use function Illuminate\Support\defer;

/**
 * Public bio pages may be kept at the edge (Cloudflare) for a minute (PublicBioController::EDGE_TTL). When a page or one
 * of its blocks changes, its copies are purged by address so the change shows at once. Everything a request or job
 * saves is collected here and sent as one queued job after the response; the save itself never waits for Cloudflare.
 * Without CLOUDFLARE_ZONE_ID and CLOUDFLARE_API_TOKEN nothing is queued and a change shows when the TTL ends.
 */
class EdgeCache
{
    /** Cloudflare takes at most this many addresses per purge call. */
    public const URLS_PER_CALL = 30;

    /** @var array<string, true> */
    private array $slugs = [];

    /** @var array<int, true> */
    private array $pageIds = [];

    public static function enabled(): bool
    {
        return filled(config('services.cloudflare.zone_id')) && filled(config('services.cloudflare.api_token'));
    }

    /** Purges the pages with these addresses (a renamed page gives both its old and its new one). */
    public static function purgeSlugs(string ...$slugs): void
    {
        if (! self::enabled()) {
            return;
        }
        $self = app(self::class);
        foreach ($slugs as $slug) {
            if ($slug !== '') {
                $self->slugs[$slug] = true;
            }
        }
        $self->schedule();
    }

    /** Purges the page a block belongs to; the page is looked up once, after the response, if it is still published. */
    public static function purgePageId(int $pageId): void
    {
        if (! self::enabled() || $pageId <= 0) {
            return;
        }
        $self = app(self::class);
        $self->pageIds[$pageId] = true;
        $self->schedule();
    }

    /**
     * Every address a page is cached under: the public host (hel.ink) and the app host (bio.hel.ink), the page itself,
     * its QR, NFC and installed-app entries (they render different click addresses) and its shop.
     *
     * @return list<string>
     */
    public static function urlsFor(string $slug): array
    {
        $hosts = array_values(array_unique(array_filter([rtrim(public_url(), '/'), rtrim((string) config('app.url'), '/')])));
        $urls = [];
        foreach ($hosts as $host) {
            $urls[] = $host.'/b/'.$slug;
            $urls[] = $host.'/b/'.$slug.'?src=qr';
            $urls[] = $host.'/b/'.$slug.'?src=nfc';
            $urls[] = $host.'/b/'.$slug.'?src=pwa';
            $urls[] = $host.'/b/shop/'.$slug;
        }

        return $urls;
    }

    private function schedule(): void
    {
        // Named: however often it is called in one request, the collection is flushed once, after the response.
        defer(fn () => $this->flush(), 'bio-edge-purge');
    }

    /** Resolves the collected pages to addresses and queues the purge, in calls of at most 30 addresses. */
    public function flush(): void
    {
        $slugs = $this->slugs;
        if ($this->pageIds !== []) {
            foreach (BioPage::whereIn('id', array_keys($this->pageIds))->where('is_published', true)->pluck('slug') as $slug) {
                $slugs[$slug] = true;
            }
        }
        $this->slugs = $this->pageIds = [];
        $urls = [];
        foreach (array_keys($slugs) as $slug) {
            array_push($urls, ...self::urlsFor((string) $slug));
        }
        foreach (array_chunk(array_values(array_unique($urls)), self::URLS_PER_CALL) as $chunk) {
            PurgeEdgeCache::dispatch($chunk);
        }
    }
}
