<?php

namespace Hop\Bio\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class BioPage extends Model
{
    // One limit for the create form, the editor and the server (L3).
    public const TITLE_MAX = 45;

    public const BIO_MAX = 155;

    use HasFactory;

    /** The four ways the top of a page can look; classic is the small centred avatar. */
    public const HEADER_LAYOUTS = ['classic', 'hero', 'banner', 'cutout'];

    /** The status chip text while it is current; null once status_until has passed. */
    public function statusLabel(): ?string
    {
        $text = trim((string) $this->status_text);
        if ($text === '' || ($this->status_until && $this->status_until->lte(now()))) {
            return null;
        }

        return $text;
    }

    public const FAB_TYPES = ['none', 'whatsapp', 'custom'];

    /** The floating button's address, or null when there is none to show. */
    public function fabHref(): ?string
    {
        $url = trim((string) $this->fab_url);
        if ($this->fab_type === 'whatsapp') {
            $digits = preg_replace('/\D+/', '', $url);

            return strlen($digits) >= 6 ? 'https://wa.me/'.$digits : null;
        }

        return $this->fab_type === 'custom' && preg_match('#^(https?://|mailto:|tel:)#i', $url) ? $url : null;
    }

    public function headerLayout(): string
    {
        return in_array($this->header_layout, self::HEADER_LAYOUTS, true) ? $this->header_layout : 'classic';
    }

    protected $fillable = [
        'user_id',
        'slug',
        'title',
        'bio',
        'status_text',
        'status_until',
        'avatar_url',
        'avatar_shape',
        'badge',
        'badge_color',
        'theme',
        'layout',
        'header_layout',
        'cover_image',
        'background_type',
        'background_value',
        'background_color',
        'background_gradient',
        'background_image',
        'text_color',
        'title_color',
        'bio_color',
        'link_bg_color',
        'link_text_color',
        'button_bg_color',
        'button_text_color',
        'button_shape',
        'button_shadow',
        'header_bg_color',
        'font_family',
        'block_shape',
        'block_shadow',
        'social_icon_color',
        'social_placement',
        'social_links',
        'socials',
        'socials_position',
        'social_icons_position',
        'seo_title',
        'seo_description',
        'og_image',
        'favicon',
        'seo_noindex',
        'custom_css',
        'is_published',
        'is_adult_content',
        'content_warning',
        'warning_reason',
        'password_enabled',
        'password',
        'is_public',
        'show_in_directory',
        'hide_branding',
        'auto_utm',
        'fallback_url',
        'footer_note',
        'footer_url',
        'privacy_mode',
        'fab_type',
        'fab_label',
        'fab_url',
        'google_analytics_id',
        'facebook_pixel_id',
        'tiktok_pixel_id',
        'gtm_id',
        'view_count',
        'allow_indexing',
        'qr_settings',
        'hover_effect',
        'background_animation',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'social_links' => 'array',
        'socials' => 'array',
        'qr_settings' => 'array',
        'is_published' => 'boolean',
        'is_adult_content' => 'boolean',
        'password_enabled' => 'boolean',
        'is_public' => 'boolean',
        'show_in_directory' => 'boolean',
        'hide_branding' => 'boolean',
        'auto_utm' => 'boolean',
        'privacy_mode' => 'boolean',
        'status_until' => 'datetime',
        'seo_noindex' => 'boolean',
        'allow_indexing' => 'boolean',
        'view_count' => 'integer',
    ];

    public const WARNING_NONE = 'none';

    public const WARNING_SENSITIVE = 'sensitive';

    public const WARNING_ADULT = 'adult';

    public function getContentWarningAttribute(?string $val): string
    {
        if ($val && in_array($val, [self::WARNING_NONE, self::WARNING_SENSITIVE, self::WARNING_ADULT], true)) {
            return $val;
        }

        return $this->is_adult_content ? self::WARNING_ADULT : self::WARNING_NONE;
    }

    public function isAdult(): bool
    {
        return $this->content_warning === self::WARNING_ADULT || (bool) $this->is_adult_content;
    }

    public function isSensitive(): bool
    {
        return $this->content_warning === self::WARNING_SENSITIVE;
    }

    public function requiresContentWarning(): bool
    {
        return $this->isAdult() || $this->isSensitive();
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(BioRevision::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(BioLink::class)->orderBy('order');
    }

    /**
     * Used by scoped route bindings ({bioPage}/links/{bioLink}).
     */
    public function bioLinks(): HasMany
    {
        return $this->hasMany(BioLink::class);
    }

    public function clicks(): HasManyThrough
    {
        return $this->hasManyThrough(BioClick::class, BioLink::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    public function incrementViews(): void
    {
        // Traffic is not an editor change; preserve the optimistic-lock timestamp.
        static::withoutTimestamps(fn () => $this->increment('view_count'));
    }

    public function getTotalClicks(): int
    {
        return $this->links()->sum('click_count');
    }

    public function getTopLinks(int $limit = 5)
    {
        return $this->links()
            ->where('is_active', true)
            ->orderByDesc('click_count')
            ->limit($limit)
            ->get();
    }
}
