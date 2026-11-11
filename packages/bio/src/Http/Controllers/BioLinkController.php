<?php

namespace Hop\Bio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AbuseFlag;
use App\Support\BioLimits;
use App\Support\CssValue;
use Hop\Bio\Contracts\UrlSafety;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Support\BioRevisions;
use Hop\Bio\Support\BioUploads;
use Hop\Bio\Support\NoteKeys;
use Hop\Bio\Support\PayBlock;
use Hop\Bio\Support\PixelHopMedia;
use Hop\Bio\Support\ProductMeta;
use Hop\Bio\Support\SingleLine;
use Hop\Safety\DeepLinks;
use Hop\Safety\SafeContent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BioLinkController extends Controller
{
    /**
     * Reject unsafe URL schemes, blacklisted or abusive destinations.
     */
    private function assertSafe(array $data): void
    {
        NoteKeys::assertNone($data);
        $safety = app(UrlSafety::class);
        $text = implode(' ', array_filter([
            $data['title'] ?? null,
            $data['description'] ?? null,
            isset($data['content']) ? strip_tags($data['content']) : null,
            // the payee name and note of a payment block go through the same content checks as any other text
            PayBlock::text($data['settings']['pay'] ?? null),
        ]));
        $checks = [
            'url' => UrlSafety::BIO_SCHEMES,
            'embed_url' => UrlSafety::WEB_SCHEMES,
            'thumbnail_url' => UrlSafety::WEB_SCHEMES,
            'custom_icon' => UrlSafety::WEB_SCHEMES,
        ];
        $errors = [];
        foreach ($checks as $field => $schemes) {
            $value = trim((string) ($data[$field] ?? ''));
            // Uploaded files are stored as site-relative paths (/storage/...)
            if ($value === '' || SafeContent::isSitePath($value)) {
                continue;
            }
            if ($reason = $safety->rejectionReason($value, $field === 'url' ? $text : '', $schemes)) {
                $errors[$field] = $reason;
            }
        }
        foreach ((array) ($data['settings']['rules'] ?? []) as $i => $rule) {
            $value = trim((string) ($rule['url'] ?? ''));
            if ($value !== '' && ($reason = $safety->rejectionReason($value, '', UrlSafety::WEB_SCHEMES))) {
                $errors["settings.rules.{$i}.url"] = $reason;
            }
        }
        foreach (['ios', 'android', 'web'] as $k) {
            $value = trim((string) ($data['settings'][$k] ?? ''));
            if ($value !== '' && ($reason = $safety->rejectionReason($value, '', UrlSafety::WEB_SCHEMES))) {
                $errors["settings.{$k}"] = $reason;
            }
        }
        // a variant's own "where to buy" address follows the same rules as the block's
        foreach ((array) ($data['settings']['variants'] ?? []) as $i => $variant) {
            $value = trim((string) ($variant['url'] ?? ''));
            if ($value !== '' && ($reason = $safety->rejectionReason($value, '', UrlSafety::WEB_SCHEMES))) {
                $errors["settings.variants.{$i}.url"] = $reason;
            }
        }
        if (! isset($errors['url']) && $text !== '') {
            $result = $safety->assess(null, $text);
            if (array_intersect($result['signals'], config('safety.block_categories', [])) !== []) {
                $errors['title'] = 'This content is not allowed due to our security policy.';
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Block changes count as a page change, so another editor tab sees a conflict (L2).
     */
    /** A carousel picture: an upload here, an https picture address, or one of the member's own PixelHop pictures. */
    private static function carouselImageRule(): array
    {
        return ['required_with:settings.images', 'string', 'max:500', fn ($attribute, $value, $fail) => preg_match('#^(/?storage/bio-images/[A-Za-z0-9._-]+|https://[^\s\'"()<>]+)$#', (string) $value)
            || (PixelHopMedia::enabled() && PixelHopMedia::isFileUrl($value)) ? null : $fail('validation.regex')->translate()];
    }

    private function touchPage(BioPage $bioPage): string
    {
        $bioPage->touch();
        // a form or poll added (or removed) changes whether a reader needs a session
        Cache::forget('bio-stateless:'.strtolower($bioPage->slug));

        return $bioPage->updated_at->toJSON();
    }

    /**
     * The editor sends the countdown target as an exact moment; store it in the app's time zone
     * so every visitor counts down to the same instant (M13).
     */
    /** Audio and PDF blocks play only files uploaded here (bio-audio/, bio-files/), never an outside address. */
    private function assertOwnMedia(array $data, ?BioLink $link = null): void
    {
        $type = $data['type'] ?? $link?->type;
        if (! in_array($type, ['audio', 'pdf'], true) || ! array_key_exists('content', $data)) {
            return;
        }
        $content = trim((string) $data['content']);
        $prefix = $type === 'audio' ? 'bio-audio' : 'bio-files';
        if ($content !== '' && ! preg_match('#^/?storage/'.$prefix.'/[A-Za-z0-9._-]+$#', $content)) {
            throw ValidationException::withMessages(['content' => 'Upload the file here; outside addresses are not played.']);
        }
    }

    /** A feed block fetches its feed once when saved, so the first visitor does not wait for it. */
    private function warmFeed(BioLink $link): void
    {
        if ($link->type === 'rss') {
            try {
                $link->feedItems(true);
            } catch (\Throwable) {
                // the page asks again later
            }
        }
    }

    /**
     * A payment block keeps only the shape PayBlock accepts. Its settings are dropped from any other block type, a new
     * payment block must carry them, and a bad value is refused with a message per field (translated).
     */
    private function normalizePay(array $data, ?BioLink $link): array
    {
        $type = $data['type'] ?? $link?->type;
        if ($type !== 'pay') {
            unset($data['settings']['pay']);

            return $data;
        }
        if (! isset($data['settings']) || ! array_key_exists('pay', (array) $data['settings'])) {
            if ($link === null) {
                throw ValidationException::withMessages(['settings.pay.format' => __('Choose a payment method.')]);
            }
            // a request that sends other settings but no payment details must not wipe the stored ones
            if (isset($data['settings']) && is_array($link->settings['pay'] ?? null)) {
                $data['settings']['pay'] = $link->settings['pay'];
            }

            return $data;
        }
        [$clean, $errors] = PayBlock::clean($data['settings']['pay']);
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
        $data['settings']['pay'] = $clean;

        return $data;
    }

    /** FAQ rows without a question are dropped and the texts trimmed; an empty list is stored as null. */
    private function normalizeFaq(array $data): array
    {
        if (array_key_exists('faq_items', $data)) {
            $items = array_values(array_filter(array_map(fn ($it) => [
                'question' => trim((string) ($it['question'] ?? '')),
                'answer' => trim((string) ($it['answer'] ?? '')),
            ], is_array($data['faq_items']) ? $data['faq_items'] : []), fn ($it) => $it['question'] !== ''));
            $data['faq_items'] = $items ?: null;
        }

        return $data;
    }

    private function normalizeCountdown(array $data): array
    {
        foreach (['countdown_date', 'publish_at', 'expire_at'] as $field) {
            if (! empty($data[$field])) {
                $data[$field] = Carbon::parse($data[$field])->setTimezone(config('app.timezone'));
            } elseif (array_key_exists($field, $data)) {
                $data[$field] = null;
            }
        }

        return $data;
    }

    public function store(Request $request, BioPage $bioPage)
    {
        $this->authorize('update', $bioPage);
        BioRevisions::maybeSnapshot($bioPage);
        $validated = $request->validate([
            'type' => 'required|in:link,image,text,header,divider,spacer,video,music,vcard,html,countdown,map,faq,youtube,spotify,soundcloud,email,code,product,collection,whatsapp,app,goal,audio,carousel,pdf,form,rss,poll,pay',
            'title' => 'nullable|string|max:100',
            'url' => 'nullable|string|max:500',
            'content' => 'nullable|string|max:5000',
            'description' => 'nullable|string|max:500',
            'thumbnail_url' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:50',
            'custom_icon' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:50',
            'btn_bg_color' => CssValue::color(30),
            'btn_text_color' => CssValue::color(30),
            'btn_border_color' => CssValue::color(30),
            'btn_icon_invert' => 'nullable|boolean',
            'settings' => 'nullable|array',
            'settings.price' => 'nullable|numeric|min:0|max:999999999',
            'settings.currency' => 'nullable|string|size:3|alpha',
            'settings.sold_out' => 'nullable|boolean',
            'settings.compare_price' => 'nullable|numeric|min:0|max:999999999',
            'settings.stock' => 'nullable|integer|min:0|max:999999',
            'settings.variants' => 'nullable|array|max:20',
            'settings.variants.*.name' => 'required_with:settings.variants|string|max:40',
            'settings.variants.*.price' => 'nullable|numeric|min:0|max:999999999',
            'settings.variants.*.stock' => 'nullable|integer|min:0|max:999999',
            'settings.variants.*.sold_out' => 'nullable|boolean',
            'settings.variants.*.url' => ['nullable', 'string', 'max:500', 'regex:#^https?://#i'],
            'settings.layout' => 'nullable|in:stack,grid',
            'settings.text' => 'nullable|string|max:500',
            'settings.pin' => ['nullable', 'regex:/^[0-9]{4,8}$/'],
            'settings.count' => 'nullable|integer|min:1|max:10',
            'settings.options' => 'nullable|array|max:6',
            'settings.options.*.text' => 'required_with:settings.options|string|max:80',
            'settings.ask_name' => 'nullable|boolean',
            'settings.ask_email' => 'nullable|boolean',
            'settings.ask_phone' => 'nullable|boolean',
            'settings.button' => 'nullable|string|max:40',
            'settings.thanks' => 'nullable|string|max:200',
            'settings.consent' => 'nullable|string|max:200',
            'settings.retention_days' => 'nullable|integer|min:1|max:365',
            'settings.rules' => 'nullable|array|max:10',
            'settings.rules.*.url' => ['required_with:settings.rules', 'string', 'max:500', 'regex:#^https?://#i'],
            'settings.rules.*.country' => ['nullable', 'string', 'max:60', 'regex:/^\s*[A-Za-z]{2}(\s*,\s*[A-Za-z]{2})*\s*$/'],
            'settings.rules.*.device' => 'nullable|in:any,mobile,tablet,desktop',
            'settings.rules.*.os' => 'nullable|in:any,ios,android',
            'settings.rules.*.from' => 'nullable|integer|min:0|max:23',
            'settings.rules.*.to' => 'nullable|integer|min:0|max:23',
            'settings.ios' => ['nullable', 'string', 'max:500', 'regex:#^https://#i'],
            'settings.android' => ['nullable', 'string', 'max:500', 'regex:#^https://#i'],
            'settings.web' => ['nullable', 'string', 'max:500', 'regex:#^https?://#i'],
            'settings.target' => 'nullable|numeric|min:0|max:999999999999',
            'settings.current' => 'nullable|numeric|min:0|max:999999999999',
            'settings.unit' => 'nullable|string|max:20',
            'settings.images' => 'nullable|array|max:10',
            'settings.images.*.url' => self::carouselImageRule(),
            'settings.images.*.caption' => 'nullable|string|max:100',
            'settings.mode' => 'nullable|in:'.implode(',', BioLink::WHATSAPP_MODES),
            'settings.numbers' => 'nullable|array|max:10',
            'settings.numbers.*.number' => ['required_with:settings.numbers', 'string', 'max:24', 'regex:/^\+?[0-9 .()-]{6,24}$/'],
            'settings.numbers.*.label' => 'nullable|string|max:40',
            'settings.numbers.*.weight' => 'nullable|integer|min:1|max:100',
            'settings.pay' => 'nullable|array',
            'publish_at' => 'nullable|date',
            'expire_at' => ['nullable', 'date', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('publish_at') && strtotime((string) $value) <= strtotime((string) $request->input('publish_at'))) {
                    $fail('The end must be after the start.');
                }
            }],
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer',
            // Advanced block fields
            'embed_url' => 'nullable|string|max:500',
            'countdown_date' => 'nullable|date',
            'countdown_label' => 'nullable|string|max:100',
            'map_address' => 'nullable|string|max:500',
            'map_zoom' => 'nullable|integer|min:10|max:18',
            'vcard_name' => ['nullable', 'string', 'max:100', new SingleLine],
            'vcard_phone' => ['nullable', 'string', 'max:30', new SingleLine],
            'vcard_email' => 'nullable|email|max:100',
            'vcard_company' => ['nullable', 'string', 'max:100', new SingleLine],
            'faq_items' => 'nullable|array',
            'faq_items.*.question' => 'nullable|string|max:500',
            'faq_items.*.answer' => 'nullable|string|max:2000',
            'html_content' => 'nullable|string|max:10000',
            'entrance_animation' => 'nullable|in:none,fade,slide-up,slide-down,pop,bounce,flip,stagger',
            'attention_animation' => 'nullable|in:none,pulse,bounce,shake,wiggle,glow,heartbeat,rainbow',
        ]);
        if ($bioPage->links()->count() >= BioLimits::maxBlocks()) {
            throw ValidationException::withMessages(['type' => 'A bio page can have at most '.BioLimits::maxBlocks().' blocks.']);
        }
        $validated = $this->normalizePay($validated, null);
        $this->assertSafe($validated);
        $this->assertOwnMedia($validated);
        $validated = $this->normalizeFaq($this->normalizeCountdown($validated));
        $maxOrder = $bioPage->links()->max('order') ?? 0;
        // Provide default titles for block types that don't require user-entered titles
        $defaultTitles = [
            'divider' => 'Divider',
            'spacer' => 'Spacer',
            'text' => 'Text Block',
            'html' => 'HTML Block',
            'code' => 'Code Block',
        ];
        $title = $validated['title'] ?? ($defaultTitles[$validated['type']] ?? null);
        $link = $bioPage->links()->create([
            'type' => $validated['type'],
            'title' => $title,
            'url' => $validated['url'] ?? null,
            'content' => $validated['content'] ?? null,
            'description' => $validated['description'] ?? null,
            'thumbnail_url' => $validated['thumbnail_url'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'custom_icon' => $validated['custom_icon'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'btn_bg_color' => $validated['btn_bg_color'] ?? null,
            'btn_text_color' => $validated['btn_text_color'] ?? null,
            'btn_border_color' => $validated['btn_border_color'] ?? null,
            'btn_icon_invert' => $validated['btn_icon_invert'] ?? false,
            'settings' => $validated['settings'] ?? null,
            'order' => $validated['order'] ?? ($maxOrder + 1),
            'is_active' => $validated['is_active'] ?? true,
            // Advanced block fields
            'embed_url' => $validated['embed_url'] ?? null,
            'countdown_date' => $validated['countdown_date'] ?? null,
            'publish_at' => $validated['publish_at'] ?? null,
            'expire_at' => $validated['expire_at'] ?? null,
            'countdown_label' => $validated['countdown_label'] ?? null,
            'map_address' => $validated['map_address'] ?? null,
            'map_zoom' => $validated['map_zoom'] ?? 15,
            'vcard_name' => $validated['vcard_name'] ?? null,
            'vcard_phone' => $validated['vcard_phone'] ?? null,
            'vcard_email' => $validated['vcard_email'] ?? null,
            'vcard_company' => $validated['vcard_company'] ?? null,
            'faq_items' => $validated['faq_items'] ?? null,
            'html_content' => $validated['html_content'] ?? null,
        ]);

        $this->warmFeed($link);

        return response()->json([
            'success' => true,
            'block' => $link,
            'page_updated_at' => $this->touchPage($bioPage),
        ]);
    }

    public function update(Request $request, BioPage $bioPage, BioLink $bioLink)
    {
        $this->authorize('update', $bioPage);
        BioRevisions::maybeSnapshot($bioPage);
        $validated = $request->validate([
            'type' => 'nullable|in:link,image,text,header,divider,spacer,video,music,vcard,html,countdown,map,faq,youtube,spotify,soundcloud,email,code,product,collection,whatsapp,app,goal,audio,carousel,pdf,form,rss,poll,pay',
            'title' => 'nullable|string|max:100',
            'url' => 'nullable|string|max:500',
            'content' => 'nullable|string|max:5000',
            'description' => 'nullable|string|max:500',
            'thumbnail_url' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:50',
            'custom_icon' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:50',
            'btn_bg_color' => CssValue::color(30),
            'btn_text_color' => CssValue::color(30),
            'btn_border_color' => CssValue::color(30),
            'btn_icon_invert' => 'nullable|boolean',
            'settings' => 'nullable|array',
            'settings.price' => 'nullable|numeric|min:0|max:999999999',
            'settings.currency' => 'nullable|string|size:3|alpha',
            'settings.sold_out' => 'nullable|boolean',
            'settings.compare_price' => 'nullable|numeric|min:0|max:999999999',
            'settings.stock' => 'nullable|integer|min:0|max:999999',
            'settings.variants' => 'nullable|array|max:20',
            'settings.variants.*.name' => 'required_with:settings.variants|string|max:40',
            'settings.variants.*.price' => 'nullable|numeric|min:0|max:999999999',
            'settings.variants.*.stock' => 'nullable|integer|min:0|max:999999',
            'settings.variants.*.sold_out' => 'nullable|boolean',
            'settings.variants.*.url' => ['nullable', 'string', 'max:500', 'regex:#^https?://#i'],
            'settings.layout' => 'nullable|in:stack,grid',
            'settings.text' => 'nullable|string|max:500',
            'settings.pin' => ['nullable', 'regex:/^[0-9]{4,8}$/'],
            'settings.count' => 'nullable|integer|min:1|max:10',
            'settings.options' => 'nullable|array|max:6',
            'settings.options.*.text' => 'required_with:settings.options|string|max:80',
            'settings.ask_name' => 'nullable|boolean',
            'settings.ask_email' => 'nullable|boolean',
            'settings.ask_phone' => 'nullable|boolean',
            'settings.button' => 'nullable|string|max:40',
            'settings.thanks' => 'nullable|string|max:200',
            'settings.consent' => 'nullable|string|max:200',
            'settings.retention_days' => 'nullable|integer|min:1|max:365',
            'settings.rules' => 'nullable|array|max:10',
            'settings.rules.*.url' => ['required_with:settings.rules', 'string', 'max:500', 'regex:#^https?://#i'],
            'settings.rules.*.country' => ['nullable', 'string', 'max:60', 'regex:/^\s*[A-Za-z]{2}(\s*,\s*[A-Za-z]{2})*\s*$/'],
            'settings.rules.*.device' => 'nullable|in:any,mobile,tablet,desktop',
            'settings.rules.*.os' => 'nullable|in:any,ios,android',
            'settings.rules.*.from' => 'nullable|integer|min:0|max:23',
            'settings.rules.*.to' => 'nullable|integer|min:0|max:23',
            'settings.ios' => ['nullable', 'string', 'max:500', 'regex:#^https://#i'],
            'settings.android' => ['nullable', 'string', 'max:500', 'regex:#^https://#i'],
            'settings.web' => ['nullable', 'string', 'max:500', 'regex:#^https?://#i'],
            'settings.target' => 'nullable|numeric|min:0|max:999999999999',
            'settings.current' => 'nullable|numeric|min:0|max:999999999999',
            'settings.unit' => 'nullable|string|max:20',
            'settings.images' => 'nullable|array|max:10',
            'settings.images.*.url' => self::carouselImageRule(),
            'settings.images.*.caption' => 'nullable|string|max:100',
            'settings.mode' => 'nullable|in:'.implode(',', BioLink::WHATSAPP_MODES),
            'settings.numbers' => 'nullable|array|max:10',
            'settings.numbers.*.number' => ['required_with:settings.numbers', 'string', 'max:24', 'regex:/^\+?[0-9 .()-]{6,24}$/'],
            'settings.numbers.*.label' => 'nullable|string|max:40',
            'settings.numbers.*.weight' => 'nullable|integer|min:1|max:100',
            'settings.pay' => 'nullable|array',
            'publish_at' => 'nullable|date',
            'expire_at' => ['nullable', 'date', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('publish_at') && strtotime((string) $value) <= strtotime((string) $request->input('publish_at'))) {
                    $fail('The end must be after the start.');
                }
            }],
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer',
            // Advanced block fields
            'embed_url' => 'nullable|string|max:500',
            'countdown_date' => 'nullable|date',
            'countdown_label' => 'nullable|string|max:100',
            'map_address' => 'nullable|string|max:500',
            'map_zoom' => 'nullable|integer|min:10|max:18',
            'vcard_name' => ['nullable', 'string', 'max:100', new SingleLine],
            'vcard_phone' => ['nullable', 'string', 'max:30', new SingleLine],
            'vcard_email' => 'nullable|email|max:100',
            'vcard_company' => ['nullable', 'string', 'max:100', new SingleLine],
            'faq_items' => 'nullable|array',
            'faq_items.*.question' => 'nullable|string|max:500',
            'faq_items.*.answer' => 'nullable|string|max:2000',
            'html_content' => 'nullable|string|max:10000',
            'entrance_animation' => 'nullable|in:none,fade,slide-up,slide-down,pop,bounce,flip,stagger',
            'attention_animation' => 'nullable|in:none,pulse,bounce,shake,wiggle,glow,heartbeat,rainbow',
        ]);
        $validated = $this->normalizePay($validated, $bioLink);
        $this->assertSafe($validated);
        $this->assertOwnMedia($validated, $bioLink);
        $validated = $this->normalizeFaq($this->normalizeCountdown($validated));
        if (! empty($validated['is_active']) && ! $bioLink->is_active && AbuseFlag::locks('bio_link', $bioLink->id)) {
            throw ValidationException::withMessages(['is_active' => 'This block was disabled by moderation.']);
        }
        // Ensure boolean fields are not null
        if (array_key_exists('btn_icon_invert', $validated) && is_null($validated['btn_icon_invert'])) {
            $validated['btn_icon_invert'] = false;
        }
        if (array_key_exists('is_active', $validated) && is_null($validated['is_active'])) {
            $validated['is_active'] = true;
        }
        $bioLink->update($validated);
        $this->warmFeed($bioLink);

        return response()->json([
            'success' => true,
            'block' => $bioLink,
            'page_updated_at' => $this->touchPage($bioPage),
        ]);
    }

    /** The entries of a form block as a CSV file (Excel-safe: cells that start like a formula are neutralised). */
    public function leadsCsv(BioPage $bioPage, BioLink $bioLink)
    {
        $this->authorize('update', $bioPage);
        abort_unless($bioLink->type === 'form', 404);
        $clean = fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v;

        return response()->streamDownload(function () use ($bioLink, $clean) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Name', 'Email', 'Phone', 'Sent at (UTC)']);
            $bioLink->leads()->orderBy('id')->chunk(500, function ($rows) use ($out, $clean) {
                foreach ($rows as $lead) {
                    fputcsv($out, [$clean($lead->name), $clean($lead->email), $clean($lead->phone), $lead->created_at?->utc()->format('Y-m-d H:i:s')]);
                }
            });
            fclose($out);
        }, 'entries-'.Str::slug($bioPage->slug.'-'.$bioLink->id).'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** The name of the app an app block's address will open on a phone, for the editor's hint; null when it is not one. */
    public function appHint(Request $request, BioPage $bioPage)
    {
        $this->authorize('update', $bioPage);
        $data = $request->validate(['url' => ['nullable', 'string', 'max:500']]);
        $url = trim((string) ($data['url'] ?? ''));
        $deep = preg_match('#^https://#i', $url) ? app(DeepLinks::class)->resolve($url) : null;

        return response()->json(['app' => $deep?->name])->header('Cache-Control', 'no-store');
    }

    /** Reads a product page's public tags (title, picture, price) so the editor can fill the product form. */
    public function fetchMeta(Request $request, BioPage $bioPage)
    {
        $this->authorize('update', $bioPage);
        $data = $request->validate(['url' => ['required', 'string', 'max:500', 'regex:#^https?://#i']]);
        if ($reason = app(UrlSafety::class)->rejectionReason($data['url'])) {
            return response()->json(['success' => false, 'message' => $reason], 422);
        }
        try {
            $meta = ProductMeta::fetch($data['url']);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage().' Type the details yourself.'], 422);
        }
        $thumb = $meta['image'] ? ProductMeta::storeImage($meta['image']) : null;
        $found = array_keys(array_filter(['title' => $meta['title'], 'description' => $meta['description'], 'picture' => $thumb, 'price' => $meta['price']]));

        return response()->json([
            'success' => (bool) $found,
            'message' => $found ? null : 'The shop shows no details to read. Type them yourself.',
            'title' => $meta['title'],
            'description' => $meta['description'],
            'price' => $meta['price'],
            'currency' => $meta['currency'],
            'thumbnail_url' => $thumb ? '/storage/'.$thumb : null,
            'found' => $found,
        ]);
    }

    /** The poll's counts for the owner: the editor shows them in the block form. */
    public function pollResults(BioPage $bioPage, BioLink $bioLink)
    {
        $this->authorize('update', $bioPage);
        abort_unless($bioLink->type === 'poll', 404);

        return response()->json($bioLink->pollResults() + ['options' => $bioLink->pollOptions()]);
    }

    public function pollReset(BioPage $bioPage, BioLink $bioLink)
    {
        $this->authorize('update', $bioPage);
        abort_unless($bioLink->type === 'poll', 404);

        return response()->json(['success' => true, 'deleted' => $bioLink->pollVotes()->delete()]);
    }

    public function leadsDestroy(BioPage $bioPage, BioLink $bioLink)
    {
        $this->authorize('update', $bioPage);
        abort_unless($bioLink->type === 'form', 404);
        $n = $bioLink->leads()->delete();

        return response()->json(['success' => true, 'deleted' => $n]);
    }

    public function destroy(BioPage $bioPage, BioLink $bioLink)
    {
        $this->authorize('update', $bioPage);
        BioRevisions::maybeSnapshot($bioPage);
        $files = BioUploads::ofLink($bioLink);
        $bioLink->delete();
        BioUploads::deleteUnused($files);

        return response()->json([
            'success' => true,
            'page_updated_at' => $this->touchPage($bioPage),
        ]);
    }

    public function reorder(Request $request, BioPage $bioPage)
    {
        $this->authorize('update', $bioPage);
        BioRevisions::maybeSnapshot($bioPage);
        $validated = $request->validate([
            'links' => 'required|array',
            'links.*.id' => 'required|exists:bio_links,id',
            'links.*.order' => 'required|integer|min:0',
        ]);
        foreach ($validated['links'] as $linkData) {
            BioLink::where('id', $linkData['id'])
                ->where('bio_page_id', $bioPage->id)
                ->update(['order' => $linkData['order']]);
        }

        return response()->json([
            'success' => true,
            'page_updated_at' => $this->touchPage($bioPage),
        ]);
    }

    public function toggle(BioPage $bioPage, BioLink $bioLink)
    {
        $this->authorize('update', $bioPage);
        BioRevisions::maybeSnapshot($bioPage);
        if (! $bioLink->is_active && AbuseFlag::locks('bio_link', $bioLink->id)) {
            return response()->json(['success' => false, 'message' => 'This block was disabled by moderation.'], 422);
        }
        $bioLink->update([
            'is_active' => ! $bioLink->is_active,
        ]);

        return response()->json([
            'success' => true,
            'is_active' => $bioLink->is_active,
            'page_updated_at' => $this->touchPage($bioPage),
        ]);
    }

    /** The block leaves the page and the list but keeps its clicks; restore() brings it back. */
    public function archive(BioPage $bioPage, BioLink $bioLink)
    {
        $this->authorize('update', $bioPage);
        BioRevisions::maybeSnapshot($bioPage);
        $bioLink->update(['archived_at' => now()]);

        return response()->json(['success' => true, 'link' => $bioLink->fresh()]);
    }

    public function restore(BioPage $bioPage, BioLink $bioLink)
    {
        $this->authorize('update', $bioPage);
        BioRevisions::maybeSnapshot($bioPage);
        $bioLink->update(['archived_at' => null, 'order' => (int) $bioPage->links()->notArchived()->max('order') + 1]);

        return response()->json(['success' => true, 'link' => $bioLink->fresh()]);
    }
}
