<?php

namespace Hop\Bio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BioWebhookDelivery extends Model
{
    protected $fillable = ['bio_page_id', 'uuid', 'event', 'payload', 'status', 'attempts', 'http_code', 'error', 'next_attempt_at'];

    protected $casts = ['next_attempt_at' => 'datetime'];

    public function bioPage(): BelongsTo
    {
        return $this->belongsTo(BioPage::class);
    }
}
