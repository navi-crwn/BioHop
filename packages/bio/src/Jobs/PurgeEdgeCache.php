<?php

namespace Hop\Bio\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Asks Cloudflare to drop the given addresses from its cache (purge by URL, at most 30 per call). A failure is logged
 * and that is all: the page is served again from the origin when its TTL ends, and the save that queued this job
 * already finished.
 */
class PurgeEdgeCache implements ShouldQueue
{
    use Queueable;

    public const ENDPOINT = 'https://api.cloudflare.com/client/v4/zones/%s/purge_cache';

    /** @param  list<string>  $urls */
    public function __construct(public array $urls) {}

    public function handle(): void
    {
        $zone = (string) config('services.cloudflare.zone_id');
        $token = (string) config('services.cloudflare.api_token');
        if ($zone === '' || $token === '' || $this->urls === []) {
            return;
        }
        try {
            $response = Http::withToken($token)->acceptJson()->timeout(10)->retry(3, 1000, throw: false)
                ->post(sprintf(self::ENDPOINT, $zone), ['files' => array_values($this->urls)]);
        } catch (\Throwable $e) {
            Log::warning('Edge cache purge: Cloudflare not reached', ['error' => $e->getMessage(), 'urls' => count($this->urls)]);

            return;
        }
        if (! $response->successful()) {
            Log::warning('Edge cache purge failed', ['status' => $response->status(), 'errors' => $response->json('errors'), 'urls' => count($this->urls)]);
        }
    }
}
