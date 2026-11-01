<?php

namespace Hop\Bio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AbuseFlag;
use App\Support\CssValue;
use Hop\Bio\Contracts\UrlSafety;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Support\BioUploads;
use Hop\Safety\SafeContent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class BioLinkController extends Controller
{
    private const MAX_BLOCKS = 100;

    /**
     * Reject unsafe URL schemes, blacklisted or abusive destinations.
     */
    private function assertSafe(array $data): void
    {
        $safety = app(UrlSafety::class);
        $text = implode(' ', array_filter([
            $data['title'] ?? null,
            $data['description'] ?? null,
            isset($data['content']) ? strip_tags($data['content']) : null,
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
    private function touchPage(BioPage $bioPage): string
    {
        $bioPage->touch();

        return $bioPage->updated_at->toJSON();
    }

    /**
     * The editor sends the countdown target as an exact moment; store it in the app's time zone
     * so every visitor counts down to the same instant (M13).
     */
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
        $validated = $request->validate([
            'type' => 'required|in:link,image,text,header,divider,spacer,video,music,vcard,html,countdown,map,faq,youtube,spotify,soundcloud,email,code,product,collection,whatsapp',
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
            'settings.mode' => 'nullable|in:'.implode(',', BioLink::WHATSAPP_MODES),
            'settings.numbers' => 'nullable|array|max:10',
            'settings.numbers.*.number' => ['required_with:settings.numbers', 'string', 'max:24', 'regex:/^\+?[0-9 .()-]{6,24}$/'],
            'settings.numbers.*.label' => 'nullable|string|max:40',
            'settings.numbers.*.weight' => 'nullable|integer|min:1|max:100',
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
            'vcard_name' => 'nullable|string|max:100',
            'vcard_phone' => 'nullable|string|max:30',
            'vcard_email' => 'nullable|email|max:100',
            'vcard_company' => 'nullable|string|max:100',
            'faq_items' => 'nullable|array',
            'faq_items.*.question' => 'nullable|string|max:500',
            'faq_items.*.answer' => 'nullable|string|max:2000',
            'html_content' => 'nullable|string|max:10000',
            'entrance_animation' => 'nullable|in:none,fade,slide-up,slide-down,pop,bounce,flip,stagger',
            'attention_animation' => 'nullable|in:none,pulse,bounce,shake,wiggle,glow,heartbeat,rainbow',
        ]);
        if ($bioPage->links()->count() >= self::MAX_BLOCKS) {
            throw ValidationException::withMessages(['type' => 'A bio page can have at most '.self::MAX_BLOCKS.' blocks.']);
        }
        $this->assertSafe($validated);
        $validated = $this->normalizeCountdown($validated);
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

        return response()->json([
            'success' => true,
            'block' => $link,
            'page_updated_at' => $this->touchPage($bioPage),
        ]);
    }

    public function update(Request $request, BioPage $bioPage, BioLink $bioLink)
    {
        $this->authorize('update', $bioPage);
        $validated = $request->validate([
            'type' => 'nullable|in:link,image,text,header,divider,spacer,video,music,vcard,html,countdown,map,faq,youtube,spotify,soundcloud,email,code,product,collection,whatsapp',
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
            'settings.mode' => 'nullable|in:'.implode(',', BioLink::WHATSAPP_MODES),
            'settings.numbers' => 'nullable|array|max:10',
            'settings.numbers.*.number' => ['required_with:settings.numbers', 'string', 'max:24', 'regex:/^\+?[0-9 .()-]{6,24}$/'],
            'settings.numbers.*.label' => 'nullable|string|max:40',
            'settings.numbers.*.weight' => 'nullable|integer|min:1|max:100',
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
            'vcard_name' => 'nullable|string|max:100',
            'vcard_phone' => 'nullable|string|max:30',
            'vcard_email' => 'nullable|email|max:100',
            'vcard_company' => 'nullable|string|max:100',
            'faq_items' => 'nullable|array',
            'faq_items.*.question' => 'nullable|string|max:500',
            'faq_items.*.answer' => 'nullable|string|max:2000',
            'html_content' => 'nullable|string|max:10000',
            'entrance_animation' => 'nullable|in:none,fade,slide-up,slide-down,pop,bounce,flip,stagger',
            'attention_animation' => 'nullable|in:none,pulse,bounce,shake,wiggle,glow,heartbeat,rainbow',
        ]);
        $this->assertSafe($validated);
        $validated = $this->normalizeCountdown($validated);
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

        return response()->json([
            'success' => true,
            'block' => $bioLink,
            'page_updated_at' => $this->touchPage($bioPage),
        ]);
    }

    public function destroy(BioPage $bioPage, BioLink $bioLink)
    {
        $this->authorize('update', $bioPage);
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
        $bioLink->update(['archived_at' => now()]);

        return response()->json(['success' => true, 'link' => $bioLink->fresh()]);
    }

    public function restore(BioPage $bioPage, BioLink $bioLink)
    {
        $this->authorize('update', $bioPage);
        $bioLink->update(['archived_at' => null, 'order' => (int) $bioPage->links()->notArchived()->max('order') + 1]);

        return response()->json(['success' => true, 'link' => $bioLink->fresh()]);
    }
}
