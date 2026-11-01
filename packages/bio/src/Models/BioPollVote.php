<?php

namespace Hop\Bio\Models;

use Illuminate\Database\Eloquent\Model;

class BioPollVote extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['bio_link_id', 'option_index', 'voter_hash'];

    protected $hidden = ['voter_hash'];

    /** A keyed hash of address and browser: enough to stop a second vote from the same device, useless for finding anyone. */
    public static function voterHash(?string $ip, ?string $userAgent): string
    {
        return substr(hash_hmac('sha256', (string) $ip.'|'.(string) $userAgent, (string) config('app.key')), 0, 40);
    }
}
