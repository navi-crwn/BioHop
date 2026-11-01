<?php

namespace Hop\Bio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One entry sent through a form block: only what the form asked for, plus a keyed hash of the sender's network address. */
class BioLead extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['bio_link_id', 'name', 'email', 'phone', 'ip_hash'];

    protected $hidden = ['ip_hash'];

    protected $casts = ['created_at' => 'datetime'];

    public function bioLink(): BelongsTo
    {
        return $this->belongsTo(BioLink::class);
    }
}
