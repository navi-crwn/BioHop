<?php

namespace Hop\Bio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BioRevision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['bio_page_id', 'label', 'blocks_count', 'snapshot'];

    protected $hidden = ['snapshot'];

    protected $casts = ['created_at' => 'datetime'];

    public function bioPage(): BelongsTo
    {
        return $this->belongsTo(BioPage::class);
    }
}
