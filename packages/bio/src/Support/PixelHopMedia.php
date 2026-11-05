<?php

namespace Hop\Bio\Support;

use App\Support\HopSignature;
use Hop\Auth\Ticket;
use Hop\Bio\Exceptions\PixelHopUnavailable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * "Choose from PixelHop": the member's own pictures (or PDFs) in PixelHop, listed server to server for the editor's
 * picker. The call is signed like every HOP App Protocol call (hop1 HMAC with the SSO secret hop/auth already holds,
 * X-HOP-App = this app, X-HOP-Actor = the member's HOP ID, which is their user id here); PixelHop answers with the
 * files of that member only. A picked file stays in PixelHop; the page keeps its public address, and only addresses
 * on the allowed PixelHop hosts are kept (isFileUrl). Nothing is uploaded, fetched or processed here.
 */
class PixelHopMedia
{
    public const PATH = '/api/v1/me/media';

    public static function enabled(): bool
    {
        return (bool) config('bio.pixelhop_picker') && self::baseUrl() !== '' && self::hosts() !== [];
    }

    public static function baseUrl(): string
    {
        return rtrim((string) (config('bio.pixelhop_url') ?: config('hop.apps.pixelhop.url')), '/');
    }

    /**
     * The origins a PixelHop file address may use: a bare host means https on that host (pixel.hel.ink); an entry
     * written as http://host[:port] allows that plain-http origin too, for a PixelHop on the developer's own machine.
     *
     * @return list<string> lower case: "host" or "http://host[:port]"
     */
    public static function hosts(): array
    {
        $hosts = [];
        foreach ((array) config('bio.pixelhop_hosts', []) as $host) {
            $host = strtolower(trim((string) $host));
            if ($host !== '' && preg_match('#^(http://)?[a-z0-9.-]+(:\d{1,5})?$#', $host)) {
                $hosts[] = $host;
            }
        }

        return $hosts;
    }

    /** An address of a file in PixelHop that a page may keep: an allowed origin (see hosts()) and a file path (/i/{id}.{ext}), nothing else. */
    public static function isFileUrl(?string $url): bool
    {
        if (! is_string($url) || $url === '' || strlen($url) > 500 || preg_match('/[\s\'"()<>\\\\]/', $url)) {
            return false;
        }
        $p = parse_url($url);
        if ($p === false || empty($p['host']) || ! in_array($p['scheme'] ?? '', ['https', 'http'], true)
            || isset($p['user']) || isset($p['pass']) || isset($p['query']) || isset($p['fragment'])) {
            return false;
        }
        $origin = strtolower($p['host']).(isset($p['port']) ? ':'.$p['port'] : '');
        $allowed = $p['scheme'] === 'https' ? ! isset($p['port']) && in_array($origin, self::hosts(), true) : in_array('http://'.$origin, self::hosts(), true);
        if (! $allowed) {
            return false;
        }

        return (bool) preg_match('#^/i/[A-Za-z0-9][A-Za-z0-9._-]{0,120}\.[a-z0-9]{1,5}$#', (string) ($p['path'] ?? ''));
    }

    /**
     * The member's files of one kind, newest first.
     *
     * @return array{items: list<array{id: string, url: string, thumb_url: string, page_url: string, width: int, height: int, bytes: int, kind: string, title: string, alt: string, album: string}>, next: string, total: int, hotlinks_blocked: bool}
     *
     * @throws PixelHopUnavailable
     */
    public function list(Authenticatable $user, string $type = 'image', string $cursor = '', int $limit = 40): array
    {
        $secrets = Ticket::resolveSecrets();
        if ($secrets === []) {
            throw new PixelHopUnavailable('HOP_SSO_SECRET is not set, so the call to PixelHop cannot be signed.');
        }
        $path = self::PATH.'?'.http_build_query(array_filter(['type' => $type, 'cursor' => $cursor, 'limit' => $limit], fn ($v) => $v !== '' && $v !== null));
        $timeout = max(1, (int) config('bio.pixelhop_timeout', 6));
        try {
            $response = Http::timeout($timeout)->connectTimeout(min(3, $timeout))->acceptJson()
                ->withHeaders(self::headers((string) config('hop-auth.app'), $secrets[0], (string) $user->getAuthIdentifier(), $path))
                ->get(self::baseUrl().$path);
        } catch (ConnectionException $e) {
            throw new PixelHopUnavailable('PixelHop did not answer.', 'unavailable', $e);
        }
        $json = $response->json();
        if ($response->status() === 404 && is_array($json) && ($json['error'] ?? null) === 'no_account') {
            throw new PixelHopUnavailable('This member has no PixelHop account yet.', 'no_account');
        }
        if (! $response->successful() || ! is_array($json) || empty($json['success']) || ! is_array($json['data']['items'] ?? null)) {
            throw new PixelHopUnavailable('PixelHop answered '.$response->status().(is_array($json) && isset($json['error']) ? ' '.$json['error'] : '').'.');
        }
        $items = [];
        $dropped = 0;
        foreach ($json['data']['items'] as $row) {
            $url = is_array($row) ? (string) ($row['url'] ?? '') : '';
            // Never offer what the save would refuse: a file address on a host that is not allowed.
            if (! self::isFileUrl($url)) {
                $dropped++;

                continue;
            }
            $items[] = [
                'id' => (string) ($row['id'] ?? ''),
                'url' => $url,
                'thumb_url' => (string) ($row['thumb_url'] ?? $url),
                'page_url' => (string) ($row['page_url'] ?? ''),
                'width' => (int) ($row['width'] ?? 0),
                'height' => (int) ($row['height'] ?? 0),
                'bytes' => (int) ($row['bytes'] ?? 0),
                'kind' => (string) ($row['kind'] ?? $type),
                'title' => (string) ($row['title'] ?? ''),
                'alt' => (string) ($row['alt'] ?? ''),
                'album' => (string) ($row['album'] ?? ''),
            ];
        }
        if ($dropped > 0) {
            Log::warning('PixelHop listed files on a host that bio.pixelhop_hosts does not allow; they were left out.', ['dropped' => $dropped, 'hosts' => self::hosts()]);
        }

        return [
            'items' => $items,
            'next' => (string) ($json['data']['next'] ?? ''),
            'total' => (int) ($json['data']['total'] ?? count($items)),
            'hotlinks_blocked' => (bool) ($json['data']['hotlinks_blocked'] ?? false),
        ];
    }

    /** @return array<string, string> the HOP App Protocol headers for a GET without a body */
    public static function headers(string $app, string $secret, string $actor, string $path): array
    {
        $ts = (string) time();
        $nonce = HopSignature::b64(random_bytes(16));

        return [
            'X-HOP-App' => $app,
            'X-HOP-Timestamp' => $ts,
            'X-HOP-Nonce' => $nonce,
            'X-HOP-Actor' => $actor,
            'X-HOP-Signature' => HopSignature::b64(hash_hmac('sha256', HopSignature::stringToSign($app, $ts, $nonce, 'GET', $path, ''), $secret, true)),
        ];
    }
}
