<?php

namespace App\Bio;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * The one place that knows LinkHop's HTTP API (link.hel.ink). Every call
 * carries the member's own API token (shared api_tokens table, scope
 * link:create). Endpoint paths and response fields are kept here so that the
 * final shape of LinkHop's API (Fase C) is a one-file change:
 *
 *   POST {url}/api/shorten   body {url, title}   → {short_url, slug?, id?, title?, url?}
 *   GET  {url}/api/links?search=…&limit=…        → {data: [{id, title, url, short_url}]} or a plain list
 */
class LinkHopClient
{
    public const SHORTEN_PATH = '/api/shorten';

    public const LINKS_PATH = '/api/links';

    public function baseUrl(): string
    {
        return rtrim((string) config('hop.apps.linkhop.url'), '/');
    }

    public function enabled(): bool
    {
        return $this->baseUrl() !== '' && (bool) config('hop.apps.linkhop.enabled', true);
    }

    /**
     * @return array{id: int|string|null, title: ?string, url: string, short_url: string, reused: bool}
     *
     * @throws LinkHopUnavailable when LinkHop is off, unreachable or answers with an error
     */
    public function shorten(string $token, string $url, ?string $title = null): array
    {
        $response = $this->send($token, fn (PendingRequest $http) => $http->post($this->baseUrl().self::SHORTEN_PATH, array_filter([
            'url' => $url,
            'title' => $title,
        ], fn ($v) => $v !== null && $v !== '')));
        $data = $this->payload($response);
        // Preferred shape: {short_url, url}. LinkHop's older /api/shorten answers {url: <short>, target_url: <target>}.
        $short = (string) ($data['short_url'] ?? (isset($data['target_url']) ? ($data['url'] ?? '') : ''));
        if ($short === '') {
            throw new LinkHopUnavailable('LinkHop answered without a short_url.', $response->status());
        }

        return [
            'id' => $data['id'] ?? $data['slug'] ?? null,
            'title' => isset($data['title']) ? (string) $data['title'] : $title,
            'url' => (string) ($data['target_url'] ?? (isset($data['short_url']) ? ($data['url'] ?? $url) : $url)),
            'short_url' => $short,
            // 200 means LinkHop found an existing link for this target; 201 that it made one.
            'reused' => $response->status() === 200 || (bool) ($data['reused'] ?? false),
        ];
    }

    /**
     * @return list<array{id: int|string, title: string, url: string, short_url: string}>
     *
     * @throws LinkHopUnavailable
     */
    public function search(string $token, string $query = '', int $limit = 10): array
    {
        $response = $this->send($token, fn (PendingRequest $http) => $http->get($this->baseUrl().self::LINKS_PATH, [
            'search' => $query,
            'limit' => $limit,
        ]));
        $data = $this->payload($response);
        $rows = isset($data['data']) && is_array($data['data']) ? $data['data'] : $data;
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row) || empty($row['short_url'])) {
                continue;
            }
            $out[] = [
                'id' => $row['id'] ?? $row['slug'] ?? $row['short_url'],
                'title' => (string) ($row['title'] ?? $row['custom_title'] ?? 'Untitled'),
                'url' => (string) ($row['url'] ?? $row['target_url'] ?? ''),
                'short_url' => (string) $row['short_url'],
            ];
        }

        return array_slice($out, 0, $limit);
    }

    /** @param  callable(PendingRequest): Response  $call */
    private function send(string $token, callable $call): Response
    {
        if (! $this->enabled()) {
            throw new LinkHopUnavailable('LinkHop is not configured (hop.apps.linkhop.url).');
        }
        try {
            $response = $call(Http::acceptJson()->withToken($token)->timeout((int) config('bio.linkhop_timeout', 8)));
        } catch (\Throwable $e) {
            throw new LinkHopUnavailable('LinkHop could not be reached: '.$e->getMessage(), 0, $e);
        }
        if ($response->status() === 401) {
            throw new LinkHopTokenRejected('LinkHop rejected the API token.', 401);
        }
        if ($response->failed()) {
            $message = (string) ($response->json('message') ?? $response->json('error') ?? 'LinkHop answered '.$response->status());
            throw new LinkHopUnavailable($message, $response->status());
        }

        return $response;
    }

    /** @return array<string, mixed> */
    private function payload(Response $response): array
    {
        $json = $response->json();

        // LinkHop wraps answers as {success, data} on some endpoints and returns the record on others.
        return is_array($json) ? (isset($json['data']) && is_array($json['data']) && ! array_is_list($json['data']) ? $json['data'] : $json) : [];
    }
}
