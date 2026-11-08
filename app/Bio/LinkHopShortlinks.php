<?php

namespace App\Bio;

use App\Models\LinkHopToken;
use Hop\Auth\Models\ApiToken;
use Hop\Bio\Contracts\ShortlinkCreator;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * The bio package's ShortlinkCreator, backed by LinkHop's API. The member's
 * service token is created on first use (hop/auth ApiToken::generate, scope
 * link:create, name "BioHop") and remembered in bio_linkhop_tokens; a token
 * revoked at the hub (401), or one without a scope BioHop now needs (403), is
 * deleted and replaced once and the call retried.
 */
class LinkHopShortlinks implements ShortlinkCreator
{
    public const TOKEN_NAME = 'BioHop';

    public const SCOPES = ['link:create', 'link:read'];

    public function __construct(private readonly LinkHopClient $client) {}

    public function findOrCreate(Authenticatable $user, string $url, ?string $title = null): array
    {
        // Links to our own sites (hel.ink and the HOP apps) are kept as typed: no second short link on top.
        if (FamilyUrls::isFamily($url)) {
            return ['id' => null, 'url' => $url, 'short_url' => $url, 'reused' => true, 'family' => true];
        }

        return $this->withToken($user, fn (string $token) => $this->client->shorten($token, $url, $title));
    }

    /** @throws LinkHopUnavailable the editor then says LinkHop cannot be reached (BioShortlinkController::search) */
    public function search(Authenticatable $user, string $query = '', int $limit = 10): array
    {
        return $this->withToken($user, fn (string $token) => $this->client->search($token, $query, $limit));
    }

    /** @template T @param  callable(string): T  $call @return T */
    private function withToken(Authenticatable $user, callable $call): mixed
    {
        $userId = (int) $user->getAuthIdentifier();
        try {
            return $call($this->token($userId));
        } catch (LinkHopTokenRejected) {
            $this->forget($userId);

            return $call($this->token($userId));
        }
    }

    /** Drops the member's stored token and its api_tokens row, so a refused token never lingers in their token list. */
    private function forget(int $userId): void
    {
        foreach (LinkHopToken::where('user_id', $userId)->get() as $row) {
            ApiToken::whereKey($row->api_token_id)->where('user_id', $userId)->where('name', self::TOKEN_NAME)->delete();
            $row->delete();
        }
    }

    /** The member's plain service token, made now if there is none (or the api_tokens row is gone). */
    private function token(int $userId): string
    {
        $row = LinkHopToken::with('apiToken')->where('user_id', $userId)->first();
        if ($row && $row->apiToken && $row->apiToken->isValid()) {
            return $row->token;
        }
        if ($row) {
            $this->forget($userId);
        }
        $made = ApiToken::generate($userId, self::TOKEN_NAME, 300, null, self::SCOPES);
        LinkHopToken::create(['user_id' => $userId, 'api_token_id' => $made['model']->id, 'token' => $made['plain_token']]);

        return $made['plain_token'];
    }
}
