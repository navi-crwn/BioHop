<?php

namespace Hop\Bio\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BioLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'bio_page_id',
        'type',
        'title',
        'url',
        'content',
        'description',
        'icon',
        'thumbnail_url',
        'brand',
        'btn_bg_color',
        'btn_text_color',
        'btn_border_color',
        'btn_icon_invert',
        'custom_icon',
        'settings',
        'is_active',
        'archived_at',
        'order',
        'click_count',
        // Advanced block fields
        'embed_url',
        'countdown_date',
        'countdown_label',
        'map_address',
        'map_zoom',
        'vcard_name',
        'vcard_phone',
        'vcard_email',
        'vcard_company',
        'faq_items',
        'html_content',
        'entrance_animation',
        'attention_animation',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'archived_at' => 'datetime',
        'btn_icon_invert' => 'boolean',
        'order' => 'integer',
        'click_count' => 'integer',
        'settings' => 'array',
        'faq_items' => 'array',
        'countdown_date' => 'datetime',
        'map_zoom' => 'integer',
    ];

    protected static function booted(): void
    {
        // Unguessable id for public click URLs (never expose the sequential id).
        static::creating(fn (BioLink $link) => $link->public_id ??= Str::random(12));
    }

    public function bioPage(): BelongsTo
    {
        return $this->belongsTo(BioPage::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(BioClick::class);
    }

    /** Shown on the page: switched on and not archived. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('archived_at');
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function recordClick(array $data): BioClick
    {
        $click = $this->clicks()->create([
            'clicked_at' => now(),
            'country' => $data['country'] ?? null,
            'device' => $data['device'] ?? null,
            'browser' => $data['browser'] ?? null,
            'referrer' => $data['referrer'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
        ]);
        $this->incrementClicks();

        return $click;
    }

    public function incrementClicks(): void
    {
        $this->increment('click_count');
    }

    /** Human name of each block type, for lists that show blocks outside the editor. */
    public const TYPE_NAMES = [
        'link' => 'Link', 'image' => 'Image', 'text' => 'Text', 'header' => 'Header',
        'divider' => 'Divider', 'spacer' => 'Spacer', 'video' => 'Video', 'youtube' => 'YouTube',
        'music' => 'Music', 'spotify' => 'Spotify', 'soundcloud' => 'SoundCloud', 'vcard' => 'Contact card',
        'html' => 'HTML', 'countdown' => 'Countdown', 'map' => 'Map', 'faq' => 'FAQ', 'product' => 'Product', 'collection' => 'Collection',
        'email' => 'Email', 'code' => 'Code',
    ];

    public function typeName(): string
    {
        return self::TYPE_NAMES[$this->type] ?? Str::headline($this->type ?: 'block');
    }

    /**
     * Main line for a block in admin lists. Many block types (text, divider, embeds, FAQ…)
     * have no title, so fall back to what the block actually holds.
     */
    public function displayTitle(): string
    {
        if (filled($this->title)) {
            return $this->title;
        }

        return match ($this->type) {
            'text' => $this->excerpt($this->content),
            'vcard' => $this->vcard_name,
            'countdown' => $this->countdown_label,
            'map' => $this->map_address,
            'faq' => $this->faq_items[0]['question'] ?? null,
            'html' => $this->excerpt($this->html_content),
            default => null,
        } ?: $this->typeName();
    }

    /** Second line: the destination or a short summary of the block's content. */
    public function displaySummary(): ?string
    {
        $detail = match ($this->type) {
            'link', 'email', 'image' => $this->url,
            'product' => trim($this->priceLabel().' · '.$this->url, ' ·'),
            'video', 'youtube', 'music', 'spotify', 'soundcloud' => $this->embed_url ?: $this->url,
            'text' => filled($this->title) ? $this->excerpt($this->content) : null,
            'vcard' => collect([$this->vcard_phone, $this->vcard_email, $this->vcard_company])->filter()->implode(' · '),
            'countdown' => $this->countdown_date?->format('j M Y, H:i'),
            'map' => filled($this->title) ? $this->map_address : null,
            'faq' => ($n = count($this->faq_items ?? [])) ? $n.' '.Str::plural('question', $n) : null,
            'code' => $this->content,
            default => $this->url,
        };
        if ($this->type === 'link') {
            return $detail ?: null;
        }

        return collect([$this->typeName(), $detail ?: null])->filter()->implode(' · ');
    }

    /** Web address the summary line should link to, when the block points somewhere. */
    public function displaySummaryUrl(): ?string
    {
        $target = match ($this->type) {
            'video', 'youtube', 'music', 'spotify', 'soundcloud' => $this->embed_url ?: $this->url,
            'link', 'image', 'product' => $this->url,
            default => null,
        };

        return $target && preg_match('#^https?://#i', $target) ? $target : null;
    }

    /**
     * "Rp 120.000" / "$ 20" for a product block, from settings.price and settings.currency; empty when there is no price.
     * With variants at different prices it is "from <lowest>".
     */
    public function priceLabel(): string
    {
        $prices = array_values(array_filter(array_map(fn ($v) => $v['price'], $this->variants()), fn ($p) => $p !== null));
        $base = $this->settings['price'] ?? null;
        if ($base !== null && $base !== '') {
            $prices[] = (float) $base;
        }
        if ($prices === []) {
            return '';
        }
        $label = $this->formatPrice(min($prices));

        return count(array_unique($prices)) > 1 ? __('from :price', ['price' => $label]) : $label;
    }

    /** The struck-through "was" price, when settings.compare_price is above the price. */
    public function comparePriceLabel(): string
    {
        $was = $this->settings['compare_price'] ?? null;
        $price = $this->settings['price'] ?? null;
        if ($was === null || $was === '' || $price === null || $price === '' || (float) $was <= (float) $price) {
            return '';
        }

        return $this->formatPrice((float) $was);
    }

    /** Sold out when switched off by hand, when the stock count reached zero, or when every variant is out. */
    public function isSoldOut(): bool
    {
        if (! empty($this->settings['sold_out'])) {
            return true;
        }
        $stock = $this->settings['stock'] ?? null;
        if ($stock !== null && $stock !== '' && (int) $stock <= 0) {
            return true;
        }
        $variants = $this->variants();

        return $variants !== [] && ! array_filter($variants, fn ($v) => ! $v['sold_out']);
    }

    /** "Only 3 left" worth of stock: the count when it is tracked and between 1 and 5, else null. */
    public function lowStock(): ?int
    {
        $stock = $this->settings['stock'] ?? null;

        return $stock !== null && $stock !== '' && (int) $stock > 0 && (int) $stock <= 5 ? (int) $stock : null;
    }

    /** @return array<int, array{name:string, price:?float, stock:?int, url:?string, sold_out:bool}> */
    public function variants(): array
    {
        $out = [];
        foreach ((array) ($this->settings['variants'] ?? []) as $v) {
            if (! is_array($v) || trim((string) ($v['name'] ?? '')) === '') {
                continue;
            }
            $stock = isset($v['stock']) && $v['stock'] !== '' ? (int) $v['stock'] : null;
            $out[] = [
                'name' => trim((string) $v['name']),
                'price' => isset($v['price']) && $v['price'] !== '' ? (float) $v['price'] : null,
                'stock' => $stock,
                'url' => ! empty($v['url']) && preg_match('#^https?://#i', (string) $v['url']) ? (string) $v['url'] : null,
                'sold_out' => ! empty($v['sold_out']) || ($stock !== null && $stock <= 0),
            ];
        }

        return $out;
    }

    public function formatPrice(float $amount): string
    {
        $currency = strtoupper((string) ($this->settings['currency'] ?? 'USD'));
        $decimals = in_array($currency, ['IDR', 'JPY', 'KRW', 'VND'], true) ? 0 : 2;
        if (class_exists(\NumberFormatter::class)) {
            $fmt = new \NumberFormatter(app()->getLocale(), \NumberFormatter::CURRENCY);
            $fmt->setAttribute(\NumberFormatter::FRACTION_DIGITS, $decimals);
            $out = $fmt->formatCurrency($amount, $currency);
            if ($out !== false) {
                return $out;
            }
        }

        return $currency.' '.number_format($amount, $decimals);
    }

    private function excerpt(?string $html): ?string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $text === '' ? null : Str::limit($text, 60);
    }
}
