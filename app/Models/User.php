<?php

namespace App\Models;

use Hop\Auth\Models\ApiToken;
use Hop\Bio\Models\BioPage;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A HOP ID, read from the shared `users` table. The hub (hub.hel.ink) is the
 * only app that writes to it: people sign in here with the hub's SSO ticket
 * (hop/auth) and change their profile, password and 2FA at the hub. BioHop
 * only reads: who someone is, whether they are suspended, which roles they hold.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    /**
     * The hub owns this table. The only write BioHop makes is abuse:scan
     * suspending a repeat offender (status); everything else is read.
     * Unguarded so tests and factories can set the columns up.
     */
    protected $guarded = [];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'show_owner' => 'boolean',
            'profile_version' => 'integer',
        ];
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    public function bioPages(): HasMany
    {
        return $this->hasMany(BioPage::class);
    }

    /**
     * Roles on HOP ID. Global roles (owner, admin, moderator, support) have no
     * app; "admin" scoped to an app makes someone admin of that app only.
     *
     * @return list<string> e.g. ["owner"], ["admin"], ["admin:biohop"]
     */
    public function roleNames(): array
    {
        $names = [];
        foreach ($this->roles as $r) {
            $names[] = $r->app_slug ? $r->role.':'.$r->app_slug : $r->role;
        }
        // SUPERADMIN_EMAIL still names the owner (same rule as the hub and LinkHop).
        $superadminEmail = config('auth.superadmin_email');
        if ($superadminEmail && strcasecmp((string) $this->email, $superadminEmail) === 0 && ! in_array(Role::OWNER, $names, true)) {
            $names[] = Role::OWNER;
        }

        return array_values(array_unique($names));
    }

    public function hasRole(string $role, ?string $app = null): bool
    {
        return in_array($app ? $role.':'.$app : $role, $this->roleNames(), true);
    }

    public function isOwner(): bool
    {
        return $this->hasRole(Role::OWNER);
    }

    /** Admin of BioHop: the HOP owner, a global admin, or an admin scoped to this app. */
    public function isAdmin(): bool
    {
        return $this->isOwner() || $this->hasRole(Role::ADMIN) || $this->hasRole(Role::ADMIN, (string) config('hop.current', 'biohop'));
    }

    /** Can moderate content: admin or moderator. */
    public function isModerator(): bool
    {
        return $this->isAdmin() || $this->hasRole(Role::MODERATOR);
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * The picture to show. The hub sends an absolute address (hub.hel.ink/storage/avatars/...), which is used as it is;
     * a bare stored path (avatar_path, or an old relative value) is put on the hub host, where the file lives.
     */
    public function avatarUrl(): ?string
    {
        return self::hubFileUrl($this->avatar) ?? self::hubFileUrl($this->avatar_path);
    }

    /** A file the hub stores: a full address stays as it is, a path (avatars/x.webp, storage/x, /storage/x) goes on the hub host. */
    public static function hubFileUrl(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (preg_match('#^(https?:)?//#i', $value) || str_starts_with($value, 'data:')) {
            return $value;
        }

        return rtrim((string) config('hop.hub_url'), '/').'/storage/'.preg_replace('#^/?(storage/)?#', '', $value);
    }

    /** Where this person manages their account: the hub. */
    public static function hubUrl(string $path = ''): string
    {
        return rtrim((string) config('hop-auth.hub_url', config('hop.hub_url')), '/').'/'.ltrim($path, '/');
    }
}
