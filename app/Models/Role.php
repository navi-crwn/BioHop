<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A role on HOP ID (shared `roles` table, written by the hub). Global roles
 * have no app_slug; "admin" with app_slug "biohop" is admin of this app only.
 */
class Role extends Model
{
    public const OWNER = 'owner';

    public const ADMIN = 'admin';

    public const MODERATOR = 'moderator';

    public const SUPPORT = 'support';

    protected $fillable = ['user_id', 'role', 'app_slug', 'granted_by'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
