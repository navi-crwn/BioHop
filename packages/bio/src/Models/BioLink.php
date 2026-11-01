<?php

namespace Hop\Bio\Models;

use Carbon\CarbonInterface;
use Hop\Bio\Support\FeedReader;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
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
        'publish_at',
        'expire_at',
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
        'publish_at' => 'datetime',
        'expire_at' => 'datetime',
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

    public function leads(): HasMany
    {
        return $this->hasMany(BioLead::class);
    }

    /** What a form block asks for: name, e-mail and phone, each on or off (name and e-mail by default). */
    public function formFields(): array
    {
        $s = $this->settings ?? [];
        $fields = [
            'name' => (bool) ($s['ask_name'] ?? true),
            'email' => (bool) ($s['ask_email'] ?? true),
            'phone' => (bool) ($s['ask_phone'] ?? false),
        ];

        return array_filter($fields) ?: ['email' => true];
    }

    /** @return array<int, array{title:string, url:string, date:?string}> the latest items of a feed block (cached an hour) */
    public function feedItems(bool $refresh = false): array
    {
        $url = trim((string) $this->url);
        if ($this->type !== 'rss' || ! preg_match('#^https?://#i', $url)) {
            return [];
        }

        return FeedReader::items($url, (int) ($this->settings['count'] ?? 5), $refresh);
    }

    /** @return array<int, string> the poll's options (2–6), empty when the poll is not ready */
    public function pollOptions(): array
    {
        $out = [];
        foreach ((array) ($this->settings['options'] ?? []) as $o) {
            $text = is_array($o) ? trim((string) ($o['text'] ?? '')) : '';
            if ($text !== '') {
                $out[] = mb_substr($text, 0, 80);
            }
        }

        return count($out) >= 2 ? array_slice($out, 0, 6) : [];
    }

    public function pollVotes(): HasMany
    {
        return $this->hasMany(BioPollVote::class);
    }

    /** @return array{counts:array<int,int>, total:int, voted:?int} votes per option and what this visitor chose (null: not voted) */
    public function pollResults(?string $voterHash = null): array
    {
        $options = $this->pollOptions();
        $counts = array_fill(0, count($options), 0);
        foreach ($this->pollVotes()->selectRaw('option_index, count(*) as n')->groupBy('option_index')->pluck('n', 'option_index') as $i => $n) {
            if (isset($counts[$i])) {
                $counts[$i] = (int) $n;
            }
        }
        $voted = $voterHash ? $this->pollVotes()->where('voter_hash', $voterHash)->value('option_index') : null;

        return ['counts' => $counts, 'total' => array_sum($counts), 'voted' => $voted === null ? null : (int) $voted];
    }

    public function leadRetentionDays(): int
    {
        return max(1, min(365, (int) ($this->settings['retention_days'] ?? 90)));
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(BioClick::class);
    }

    /** Shown on the page: switched on and not archived. */
    /** Shown on the page: switched on, not archived, and inside its schedule window (if any). */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('archived_at')
            ->where(fn ($q) => $q->whereNull('publish_at')->orWhere('publish_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expire_at')->orWhere('expire_at', '>', now()));
    }

    /** Inside the schedule window: not before publish_at and not after expire_at. */
    public function isLive(): bool
    {
        return (! $this->publish_at || $this->publish_at->lte(now())) && (! $this->expire_at || $this->expire_at->gt(now()));
    }

    /** "scheduled" before publish_at, "expired" after expire_at, null when live. */
    public function scheduleState(): ?string
    {
        if ($this->publish_at && $this->publish_at->gt(now())) {
            return 'scheduled';
        }

        return $this->expire_at && $this->expire_at->lte(now()) ? 'expired' : null;
    }

    public const WHATSAPP_MODES = ['round_robin', 'weighted'];

    /** @return array<int, array{country:array<int,string>, device:string, os:string, from:?int, to:?int, url:string}> */
    public function routingRules(): array
    {
        $out = [];
        foreach ((array) ($this->settings['rules'] ?? []) as $r) {
            $url = is_array($r) ? trim((string) ($r['url'] ?? '')) : '';
            if (! preg_match('#^https?://#i', $url)) {
                continue;
            }
            $countries = array_values(array_filter(array_map(fn ($c) => strtoupper(trim($c)), explode(',', (string) ($r['country'] ?? ''))), fn ($c) => preg_match('/^[A-Z]{2}$/', $c)));
            $from = isset($r['from']) && $r['from'] !== '' && $r['from'] !== null ? (int) $r['from'] : null;
            $to = isset($r['to']) && $r['to'] !== '' && $r['to'] !== null ? (int) $r['to'] : null;
            $out[] = [
                'country' => $countries,
                'device' => in_array($r['device'] ?? 'any', ['mobile', 'tablet', 'desktop'], true) ? $r['device'] : 'any',
                'os' => in_array($r['os'] ?? 'any', ['ios', 'android'], true) ? $r['os'] : 'any',
                'from' => $from !== null && $to !== null ? $from : null,
                'to' => $from !== null && $to !== null ? $to : null,
                'url' => $url,
            ];
        }

        return $out;
    }

    /**
     * The first rule whose conditions all hold decides the address: a rule with a country list needs the visitor's country,
     * one with hours needs the time in the owner's zone, the others need only the device or system. Null when none matches.
     *
     * @param  array{device?:string, os?:string}  $ua
     * @param  callable():?string  $country  looked up only when a rule asks for it
     */
    public function routedUrl(array $ua, callable $country, CarbonInterface $now): ?string
    {
        $device = strtolower((string) ($ua['device'] ?? ''));
        $os = strtolower((string) ($ua['os'] ?? ''));
        $isIos = (bool) preg_match('/ios|ipados/', $os);
        $isAndroid = str_contains($os, 'android');
        $code = null;
        $looked = false;
        foreach ($this->routingRules() as $rule) {
            if ($rule['device'] !== 'any' && $rule['device'] !== $device) {
                continue;
            }
            if (($rule['os'] === 'ios' && ! $isIos) || ($rule['os'] === 'android' && ! $isAndroid)) {
                continue;
            }
            if ($rule['from'] !== null) {
                $h = (int) $now->format('G');
                $inside = $rule['from'] <= $rule['to'] ? ($h >= $rule['from'] && $h < $rule['to']) : ($h >= $rule['from'] || $h < $rule['to']);
                if (! $inside) {
                    continue;
                }
            }
            if ($rule['country'] !== []) {
                if (! $looked) {
                    $code = $country();
                    $looked = true;
                }
                if (! $code || ! in_array(strtoupper($code), $rule['country'], true)) {
                    continue;
                }
            }

            return $rule['url'];
        }

        return null;
    }

    /** The app block's address for a visitor's operating system: iOS → App Store, Android → Play, else the web address. */
    public function appTarget(string $os): ?string
    {
        $s = $this->settings ?? [];
        $pick = preg_match('/ios|ipados/i', $os) ? ($s['ios'] ?? null) : (preg_match('/android/i', $os) ? ($s['android'] ?? null) : null);
        $url = trim((string) ($pick ?: ($s['web'] ?? $this->url ?: ($s['ios'] ?? $s['android'] ?? ''))));

        return preg_match('#^https?://#i', $url) ? $url : null;
    }

    /** 0–100 for a goal block. */
    public function goalPercent(): int
    {
        $target = (float) ($this->settings['target'] ?? 0);
        $current = (float) ($this->settings['current'] ?? 0);

        return $target > 0 ? (int) max(0, min(100, round($current / $target * 100))) : 0;
    }

    /** @return array<int, array{url:string, caption:string}> */
    public function carouselImages(): array
    {
        $out = [];
        foreach ((array) ($this->settings['images'] ?? []) as $i) {
            $url = is_array($i) ? trim((string) ($i['url'] ?? '')) : '';
            if ($url !== '' && (preg_match('#^/?storage/bio-images/#', $url) || preg_match('#^https://#', $url))) {
                $out[] = ['url' => $url, 'caption' => trim((string) ($i['caption'] ?? ''))];
            }
        }

        return $out;
    }

    /** @return array<int, array{number:string, label:string, weight:int}> the rotator's numbers, digits only */
    public function whatsappNumbers(): array
    {
        $out = [];
        foreach ((array) ($this->settings['numbers'] ?? []) as $n) {
            $digits = preg_replace('/\D+/', '', (string) ($n['number'] ?? ''));
            if (is_array($n) && strlen($digits) >= 6) {
                $out[] = ['number' => $digits, 'label' => trim((string) ($n['label'] ?? '')), 'weight' => max(1, (int) ($n['weight'] ?? 1))];
            }
        }

        return $out;
    }

    /**
     * The wa.me address for this click: round robin (one counter per block in the cache) or weighted random.
     * Null when the block has no usable number.
     */
    public function whatsappTarget(): ?string
    {
        $numbers = $this->whatsappNumbers();
        if ($numbers === []) {
            return null;
        }
        if (($this->settings['mode'] ?? 'round_robin') === 'weighted' && count($numbers) > 1) {
            $total = array_sum(array_column($numbers, 'weight'));
            $pick = random_int(1, $total);
            $chosen = $numbers[0];
            foreach ($numbers as $n) {
                $pick -= $n['weight'];
                if ($pick <= 0) {
                    $chosen = $n;
                    break;
                }
            }
        } else {
            $i = Cache::increment('wa_rotator:'.$this->id) - 1;
            $chosen = $numbers[$i % count($numbers)];
        }
        $text = trim((string) ($this->settings['text'] ?? ''));

        return 'https://wa.me/'.$chosen['number'].($text !== '' ? '?text='.rawurlencode($text) : '');
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
            'source' => $data['source'] ?? null,
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
        'html' => 'HTML', 'countdown' => 'Countdown', 'map' => 'Map', 'faq' => 'FAQ', 'product' => 'Product', 'collection' => 'Collection', 'whatsapp' => 'WhatsApp', 'app' => 'App', 'form' => 'Form', 'rss' => 'Feed', 'poll' => 'Poll', 'goal' => 'Goal', 'audio' => 'Audio', 'carousel' => 'Carousel', 'pdf' => 'PDF',
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
