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
 * revoked at the hub (401) is replaced once and the call retried.
 */
class LinkHopShortlinks implements ShortlinkCreator
{
    public const TOKEN_NAME = 'BioHop';

    public const SCOPES = ['link:create', 'link:read'];

    public function __construct(private readonly LinkHopClient $client) {}

    public function findOrCreate(Authenticatable $user, string $url, ?string $title = null): array
    {
        return $this->withToken($user, fn (string $token) => $this->client->shorten($token, $url, $title));
    }

    public function search(Authenticatable $user, string $query = '', int $limit = 10): array
    {
        try {
            return $this->withToken($user, fn (string $token) => $this->client->search($token, $query, $limit));
        } catch (LinkHopUnavailable $e) {
            report($e);

            return []; // the editor just shows no suggestions
        }
    }

    /** @template T @param  callable(string): T  $call @return T */
    private function withToken(Authenticatable $user, callable $call): mixed
    {
        $userId = (int) $user->getAuthIdentifier();
        try {
            return $call($this->token($userId));
        } catch (LinkHopTokenRejected) {
            LinkHopToken::where('user_id', $userId)->delete();

            return $call($this->token($userId));
        }
    }

    /** The member's plain service token, made now if there is none (or the api_tokens row is gone). */
    private function token(int $userId): string
    {
        $row = LinkHopToken::with('apiToken')->where('user_id', $userId)->first();
        if ($row && $row->apiToken && $row->apiToken->isValid()) {
            return $row->token;
        }
        $row?->delete();
        $made = ApiToken::generate($userId, self::TOKEN_NAME, 300, null, self::SCOPES);
        LinkHopToken::create(['user_id' => $userId, 'api_token_id' => $made['model']->id, 'token' => $made['plain_token']]);

        return $made['plain_token'];
    }
}
