<?php

namespace App\Models;

use Hop\Auth\Models\ApiToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The member's service token for LinkHop's API, made by BioHop on first use
 * (shared api_tokens row with scope link:create) and kept here encrypted,
 * because api_tokens only stores the hash. Table bio_linkhop_tokens is BioHop's.
 */
class LinkHopToken extends Model
{
    protected $table = 'bio_linkhop_tokens';

    protected $fillable = ['user_id', 'api_token_id', 'token'];

    protected function casts(): array
    {
        return ['token' => 'encrypted'];
    }

    public function apiToken(): BelongsTo
    {
        return $this->belongsTo(ApiToken::class, 'api_token_id');
    }
}
