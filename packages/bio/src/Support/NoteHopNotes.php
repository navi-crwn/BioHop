<?php

namespace Hop\Bio\Support;

use Hop\Bio\Exceptions\NoteHopUnavailable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * "Choose from NoteHop": the member's public NoteHop notes, listed server to server for the link block's picker
 * (BIO_NOTEHOP_PICKER). Signed like every HOP App Protocol call (hop1 HMAC with HOP_PICKER_SECRET, or else the SSO secret hop/auth holds,
 * X-HOP-App = this app, X-HOP-Actor = the member's HOP ID); NoteHop answers with that member's notes only.
 *
 * NoteHop lists public notes that have a read-only link and nothing else: an encrypted note opens only with the key
 * in the address fragment, which never leaves the member's browser, so it is never offered. Here every address is
 * checked again before it reaches the editor: NoteHop's own origin, a bare note path, no query, no fragment, and no
 * note key anywhere (NoteKeys). The block keeps the address; NoteHop keeps the note.
 */
class NoteHopNotes
{
    public const PATH = '/api/v1/me/notes';

    /**
     * Always false: the picker is retired (a note is shared from NoteHop itself, and the Text block writes on a page), so no
     * button, dialog or route answers, whatever BIO_NOTEHOP_PICKER says. Kept as a method so the code that is left over
     * (the proxy route, listNotes) can be removed in one later step; isNoteUrl below is still used by address checks.
     */
    public static function enabled(): bool
    {
        return false;
    }

    public static function baseUrl(): string
    {
        $url = rtrim((string) config('bio.notehop_url'), '/');

        return preg_match('#^https?://[a-z0-9.-]+(:\d{1,5})?$#i', $url) ? $url : '';
    }

    /** A note address a block may keep: NoteHop's origin and /{token}, nothing else. */
    public static function isNoteUrl(?string $url): bool
    {
        if (! is_string($url) || $url === '' || strlen($url) > 300 || NoteKeys::holds($url)) {
            return false;
        }
        $base = self::baseUrl();
        if ($base === '' || ! str_starts_with(strtolower($url), strtolower($base).'/')) {
            return false;
        }

        return (bool) preg_match('#^/[A-Za-z0-9][A-Za-z0-9_-]{2,63}$#', substr($url, strlen($base)));
    }

    /**
     * @return array{items: list<array{id: string, title: string, url: string, updated: ?string}>, next: string}
     *
     * @throws NoteHopUnavailable
     */
    public function list(Authenticatable $user, string $query = '', string $cursor = '', int $limit = 20): array
    {
        $secret = PickerSecret::signing();
        if ($secret === null) {
            throw new NoteHopUnavailable('Neither HOP_PICKER_SECRET nor HOP_SSO_SECRET is set, so the call to NoteHop cannot be signed.');
        }
        $path = self::PATH.'?'.http_build_query(array_filter(['q' => $query, 'cursor' => $cursor, 'limit' => $limit], fn ($v) => $v !== '' && $v !== null));
        $timeout = max(1, (int) config('bio.notehop_timeout', 6));
        try {
            $response = Http::timeout($timeout)->connectTimeout(min(3, $timeout))->acceptJson()->withoutRedirecting()
                ->withHeaders(PixelHopMedia::headers((string) config('hop-auth.app'), $secret, (string) $user->getAuthIdentifier(), $path))
                ->get(self::baseUrl().$path);
        } catch (ConnectionException $e) {
            throw new NoteHopUnavailable('NoteHop did not answer.', 'unavailable', $e);
        }
        $json = $response->json();
        if ($response->status() === 404 && is_array($json) && ($json['error'] ?? null) === 'no_account') {
            throw new NoteHopUnavailable('This member has not signed in to NoteHop yet.', 'no_account');
        }
        if (! $response->successful() || ! is_array($json) || ($json['ok'] ?? false) !== true || ! is_array($json['data']['items'] ?? null)) {
            throw new NoteHopUnavailable('NoteHop answered '.$response->status().(is_array($json) && isset($json['error']) ? ' '.$json['error'] : '').'.');
        }
        $items = [];
        $dropped = 0;
        foreach ($json['data']['items'] as $row) {
            $url = is_array($row) ? (string) ($row['url'] ?? '') : '';
            if (! self::isNoteUrl($url)) {
                $dropped++;

                continue;
            }
            $items[] = [
                'id' => (string) ($row['id'] ?? ''),
                'title' => mb_substr(trim((string) ($row['title'] ?? '')), 0, 200),
                'url' => $url,
                'updated' => is_string($row['updated'] ?? null) ? $row['updated'] : null,
            ];
        }
        if ($dropped > 0) {
            Log::warning('NoteHop listed note addresses this app does not accept; they were left out.', ['dropped' => $dropped, 'base' => self::baseUrl()]);
        }
        $next = (string) ($json['data']['next'] ?? '');

        return ['items' => $items, 'next' => ctype_digit($next) ? $next : ''];
    }
}
