<?php

namespace Hop\Bio\Http\Controllers;

use App\Helpers\SocialUrlHelper;
use App\Http\Controllers\Controller;
use App\Models\AbuseFlag;
use App\Support\CssValue;
use App\Support\ReservedSlugs;
use Carbon\Carbon;
use Hop\Bio\Contracts\UrlSafety;
use Hop\Bio\Models\BioClick;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Support\BioUploads;
use Hop\Bio\Support\LinktreeImport;
use Hop\Bio\Support\SvgSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BioPageController extends Controller
{
    private const ANALYTICS_RANGES = [7, 30];

    public const MAX_PAGES = 3;

    public function index()
    {
        $bioPages = auth()->user()->bioPages()->withCount('links')->with('links:id,bio_page_id')->latest()->get();
        $home = self::dashboardHome($bioPages);

        return view('bio::index', compact('bioPages') + $home);
    }

    /**
     * Numbers for the dashboard home: clicks per day for the last 14 days (this week against last week,
     * and a 7-day line per page) and the setup checklist of the newest page. Views are a plain counter,
     * so the week figures are clicks: the logs we keep (30 days).
     *
     * @return array{weekClicks:int, weekDelta:int|null, week:array<int,int>, perPage:array<int,array<int,int>>, checklist:?array{page:BioPage, items:array<int,array{key:string,label:string,done:bool,tab:string}>, done:int}}
     */
    public static function dashboardHome($bioPages): array
    {
        $linkToPage = [];
        foreach ($bioPages as $page) {
            foreach ($page->links as $link) {
                $linkToPage[$link->id] = $page->id;
            }
        }
        $perDay = [];  // page id => day offset (0 = today) => clicks
        $since = now()->startOfDay()->subDays(13);
        if ($linkToPage !== []) {
            $rows = BioClick::query()->whereIn('bio_link_id', array_keys($linkToPage))->where('clicked_at', '>=', $since)
                ->selectRaw('bio_link_id, DATE(clicked_at) as day, COUNT(*) as n')->groupBy('bio_link_id', 'day')->get();
            foreach ($rows as $row) {
                $offset = (int) now()->startOfDay()->diffInDays(Carbon::parse($row->day)->startOfDay(), true);
                $perDay[$linkToPage[$row->bio_link_id]][$offset] = ($perDay[$linkToPage[$row->bio_link_id]][$offset] ?? 0) + (int) $row->n;
            }
        }
        $week = array_fill(0, 7, 0);
        $thisWeek = $lastWeek = 0;
        $perPage = [];
        foreach ($bioPages as $page) {
            $line = [];
            for ($i = 6; $i >= 0; $i--) {
                $n = $perDay[$page->id][$i] ?? 0;
                $line[] = $n;
                $week[6 - $i] += $n;
                $thisWeek += $n;
            }
            for ($i = 7; $i <= 13; $i++) {
                $lastWeek += $perDay[$page->id][$i] ?? 0;
            }
            $perPage[$page->id] = $line;
        }
        $checklist = null;
        if ($first = $bioPages->first()) {
            $items = [
                ['key' => 'avatar', 'label' => __('Add a profile picture'), 'done' => (bool) $first->avatar_url, 'tab' => 'profile'],
                ['key' => 'bio', 'label' => __('Write a short bio'), 'done' => trim((string) $first->bio) !== '', 'tab' => 'profile'],
                ['key' => 'link', 'label' => __('Add your first link'), 'done' => $first->links_count > 0, 'tab' => 'links'],
                ['key' => 'socials', 'label' => __('Add your social icons'), 'done' => collect($first->social_links ?? [])->contains(fn ($s) => trim((string) (($s['value'] ?? '') ?: ($s['url'] ?? ''))) !== ''), 'tab' => 'socials'],
                ['key' => 'design', 'label' => __('Pick a theme or your own colours'), 'done' => ($first->theme && $first->theme !== 'default') || $first->headerLayout() !== 'classic' || (string) $first->background_value !== '' || ! in_array($first->font_family, [null, '', 'default', 'inter'], true), 'tab' => 'appearance'],
                ['key' => 'publish', 'label' => __('Publish the page'), 'done' => (bool) $first->is_published, 'tab' => 'settings'],
            ];
            $checklist = ['page' => $first, 'items' => $items, 'done' => count(array_filter($items, fn ($i) => $i['done']))];
        }

        return [
            'weekClicks' => $thisWeek,
            'weekDelta' => $lastWeek > 0 ? (int) round(($thisWeek - $lastWeek) / $lastWeek * 100) : null,
            'week' => $week,
            'perPage' => $perPage,
            'checklist' => $checklist,
        ];
    }

    public function create(Request $request)
    {
        // The wizard builds a finished page in five steps; ?classic=1 keeps the plain form.
        if ($request->boolean('classic')) {
            return view('bio::create');
        }

        return view('bio::create-wizard', [
            'platforms' => collect(config('brands.platforms', []))->only(self::WIZARD_PLATFORMS)->map(fn ($p, $id) => ['id' => $id, 'name' => $p['name'], 'color' => $p['color'] ?? '#6b7280', 'placeholder' => $p['placeholder'] ?? ''])->values(),
            'palettes' => self::WIZARD_PALETTES,
            'slugMin' => auth()->user()?->isAdmin() ? 3 : (int) config('bio.slug_min_length', 5),
        ]);
    }

    /** The platforms the wizard offers first; everything else can be added in the editor. */
    public const WIZARD_PLATFORMS = ['instagram', 'whatsapp', 'tiktok', 'youtube', 'website', 'spotify', 'threads', 'facebook', 'x', 'telegram', 'email', 'linkedin'];

    /** Wallpaper and button pairs the wizard offers: [wallpaper, button, button text]. */
    public const WIZARD_PALETTES = [
        ['#2b1120', '#f4c7d6', '#2b1120'], ['#1f2a14', '#d6e8a8', '#1f2a14'], ['#f6d6e8', '#5b1f47', '#ffffff'], ['#f8dcc4', '#8a3b12', '#ffffff'],
        ['#1a120c', '#e8c39e', '#1a120c'], ['#dfe8c8', '#2f4a1c', '#ffffff'], ['#0b1b2b', '#9ad0ff', '#0b1b2b'], ['#ffffff', '#111111', '#ffffff'],
        ['#000000', '#ffffff', '#000000'], ['#eef2ff', '#4f46e5', '#ffffff'],
    ];

    /** One request from the wizard: a page with its socials, first links, profile and colours. */
    public function storeWizard(Request $request)
    {
        if (auth()->user()->bioPages()->count() >= self::MAX_PAGES) {
            return response()->json(['message' => 'You have reached the maximum limit of '.self::MAX_PAGES.' bio pages.'], 422);
        }
        $request->merge(['slug' => strtolower((string) $request->slug)]);
        $validated = $request->validate([
            'slug' => $this->slugRules(),
            'title' => 'required|string|max:'.BioPage::TITLE_MAX,
            'bio' => 'nullable|string|max:'.BioPage::BIO_MAX,
            'goal' => 'nullable|in:creator,business,personal',
            'platforms' => 'nullable|array|max:12',
            'platforms.*.platform' => 'required_with:platforms|string|max:40',
            'platforms.*.value' => 'nullable|string|max:255',
            'links' => 'nullable|array|max:10',
            'links.*' => 'nullable|string|max:500',
            'palette' => 'nullable|integer|min:0|max:'.(count(self::WIZARD_PALETTES) - 1),
            'avatar' => 'nullable|image|max:2048',
            'content_warning' => 'nullable|in:none,sensitive,adult',
        ]);
        $this->assertSafeContent($validated);
        $safety = app(UrlSafety::class);
        $brands = config('brands.platforms', []);
        $socials = [];
        foreach ($validated['platforms'] ?? [] as $i => $social) {
            $platform = $social['platform'];
            $value = trim((string) ($social['value'] ?? ''));
            if (! isset($brands[$platform]) || $value === '') {
                continue;
            }
            $url = SocialUrlHelper::buildUrl($platform, $value);
            if ($safety->scheme($url) !== null && ($reason = $safety->rejectionReason($url, '', UrlSafety::BIO_SCHEMES))) {
                throw ValidationException::withMessages(["platforms.{$i}.value" => $reason]);
            }
            $socials[] = ['platform' => $platform, 'value' => $value, 'enabled' => true];
        }
        $links = [];
        foreach (array_values(array_filter($validated['links'] ?? [], fn ($u) => trim((string) $u) !== '')) as $i => $url) {
            $url = trim($url);
            if (! preg_match('#^https?://#i', $url)) {
                $url = 'https://'.$url;
            }
            if ($reason = $safety->rejectionReason($url)) {
                throw ValidationException::withMessages(["links.{$i}" => $reason]);
            }
            $brand = self::brandForUrl($url, $brands);
            $links[] = ['url' => $url, 'brand' => $brand, 'title' => $brand ? $brands[$brand]['name'] : self::hostTitle($url)];
        }
        $palette = isset($validated['palette']) ? self::WIZARD_PALETTES[(int) $validated['palette']] : null;
        $goalTheme = ['creator' => 'galaxy', 'business' => 'minimal-light', 'personal' => 'sunset'][$validated['goal'] ?? ''] ?? 'default';

        $bioPage = auth()->user()->bioPages()->create([
            'slug' => $validated['slug'],
            'title' => $validated['title'],
            'bio' => $validated['bio'] ?? null,
            'content_warning' => $validated['content_warning'] ?? 'none',
            'is_adult_content' => ($validated['content_warning'] ?? 'none') === 'adult',
            'theme' => $palette ? 'none' : $goalTheme,
            'background_type' => $palette ? 'solid' : null,
            'background_value' => $palette[0] ?? null,
            'background_color' => $palette[0] ?? null,
            'link_bg_color' => $palette[1] ?? null,
            'link_text_color' => $palette[2] ?? null,
            'button_bg_color' => $palette[1] ?? null,
            'button_text_color' => $palette[2] ?? null,
            'text_color' => $palette ? $palette[1] : null,
            'title_color' => $palette ? $palette[1] : null,
            'bio_color' => $palette ? $palette[1] : null,
            'social_links' => $socials,
            'button_shape' => 'pill',
        ]);
        if ($request->hasFile('avatar')) {
            $bioPage->update(['avatar_url' => $this->storeImageOrSvg($request, 'avatar', 'avatars')]);
        }
        foreach ($links as $i => $link) {
            $bioPage->links()->create(['type' => 'link', 'title' => $link['title'], 'url' => $link['url'], 'brand' => $link['brand'], 'order' => $i, 'is_active' => true]);
        }

        return response()->json(['success' => true, 'redirect' => route('bio.edit', $bioPage), 'url' => public_url('b/'.$bioPage->slug)]);
    }

    public function importForm()
    {
        return view('bio::import', ['slugMin' => auth()->user()?->isAdmin() ? 3 : (int) config('bio.slug_min_length', 5)]);
    }

    /** Reads a public Linktree page into a new draft page: title, bio, picture, links, groups, headers and social icons. */
    public function import(Request $request)
    {
        if (auth()->user()->bioPages()->count() >= self::MAX_PAGES) {
            return back()->withInput()->withErrors(['linktree' => 'You have reached the maximum limit of '.self::MAX_PAGES.' bio pages.']);
        }
        $request->merge(['slug' => strtolower((string) $request->slug)]);
        $validated = $request->validate([
            'linktree' => 'required|string|max:200',
            'slug' => $this->slugRules(),
        ]);
        $username = LinktreeImport::username($validated['linktree']);
        if (! $username) {
            return back()->withInput()->withErrors(['linktree' => __('Enter your Linktree address, like linktr.ee/yourname.')]);
        }
        try {
            $page = LinktreeImport::fetch($username);
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['linktree' => $e->getMessage()]);
        }
        $safety = app(UrlSafety::class);
        $brands = config('brands.platforms', []);
        $socials = [];
        foreach ($page['socials'] as $social) {
            $url = SocialUrlHelper::buildUrl($social['platform'], $social['value']);
            if ($safety->scheme($url) === null || ! $safety->rejectionReason($url, '', UrlSafety::BIO_SCHEMES)) {
                $socials[] = ['platform' => $social['platform'], 'value' => $social['value'], 'enabled' => true];
            }
        }
        $bioPage = auth()->user()->bioPages()->create([
            'slug' => $validated['slug'],
            'title' => $page['title'],
            'bio' => $page['bio'],
            'theme' => 'default',
            'social_links' => $socials,
            'is_published' => false,
            'content_warning' => 'none',
        ]);
        if ($picture = LinktreeImport::storePicture($page['picture'])) {
            $bioPage->update(['avatar_url' => $picture]);
        }
        $order = 0;
        $skipped = 0;
        $make = function (array $link, string $type) use ($bioPage, $safety, $brands, &$order, &$skipped) {
            if ($link['url'] && $safety->rejectionReason($link['url'])) {
                $skipped++;

                return;
            }
            $bioPage->links()->create([
                'type' => $type,
                'title' => Str::limit($link['title'] ?: ($link['url'] ? self::hostTitle($link['url']) : ''), 100, ''),
                'url' => $link['url'],
                'brand' => $link['url'] ? self::brandForUrl($link['url'], $brands) : null,
                'thumbnail_url' => $link['thumbnail'] ?? null,
                'order' => $order++,
                'is_active' => true,
                'settings' => $type === 'collection' ? ['layout' => 'stack'] : null,
            ]);
        };
        foreach ($page['links'] as $link) {
            $make($link, $link['type']);
            foreach ($link['children'] as $child) {
                $make($child + ['children' => []], 'link');
            }
        }
        $count = $bioPage->links()->count();
        $message = __('Imported :links blocks and :socials social icons from linktr.ee/:name. The page stays a draft until you publish it.', ['links' => $count, 'socials' => count($socials), 'name' => $page['username']]);
        if ($skipped) {
            $message .= ' '.__(':count links were left out because their addresses are blocked here.', ['count' => $skipped]);
        }

        return redirect()->route('bio.edit', $bioPage)->with('success', $message);
    }

    /** The brand whose placeholder host matches the address, so "youtube.com/@x" becomes a YouTube button. */
    public static function brandForUrl(string $url, array $brands): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^(www|m|mobile)\./', '', $host);
        if ($host === '') {
            return null;
        }
        // hosts whose placeholder is a handle rather than an address
        $known = ['youtube.com' => 'youtube', 'youtu.be' => 'youtube', 'instagram.com' => 'instagram', 'tiktok.com' => 'tiktok', 'x.com' => 'x', 'twitter.com' => 'x',
            'facebook.com' => 'facebook', 'fb.com' => 'facebook', 'threads.net' => 'threads', 'threads.com' => 'threads', 'open.spotify.com' => 'spotify', 'spotify.com' => 'spotify',
            'linkedin.com' => 'linkedin', 't.me' => 'telegram', 'telegram.me' => 'telegram', 'wa.me' => 'whatsapp', 'whatsapp.com' => 'whatsapp', 'github.com' => 'github',
            'twitch.tv' => 'twitch', 'pinterest.com' => 'pinterest', 'snapchat.com' => 'snapchat', 'discord.gg' => 'discord', 'discord.com' => 'discord', 'soundcloud.com' => 'soundcloud',
            'medium.com' => 'medium', 'substack.com' => 'substack', 'behance.net' => 'behance', 'dribbble.com' => 'dribbble', 'patreon.com' => 'patreon', 'gumroad.com' => 'gumroad', 'etsy.com' => 'etsy', 'shopee.co.id' => 'shop', 'tokopedia.com' => 'shop'];
        foreach ($known as $kh => $kid) {
            if (($host === $kh || str_ends_with($host, '.'.$kh)) && isset($brands[$kid])) {
                return $kid;
            }
        }
        foreach ($brands as $id => $brand) {
            $bh = strtolower((string) parse_url($brand['placeholder'] ?? '', PHP_URL_HOST));
            $bh = preg_replace('/^(www|m)\./', '', $bh);
            if ($bh !== '' && $bh !== null && ($host === $bh || str_ends_with($host, '.'.$bh))) {
                return $id;
            }
        }

        return null;
    }

    private static function hostTitle(string $url): string
    {
        $host = preg_replace('/^www\./', '', strtolower((string) parse_url($url, PHP_URL_HOST)));

        return ucfirst(explode('.', $host)[0] ?: 'Link');
    }

    public function checkSlug(Request $request)
    {
        // Same rules as store() and update(), so "available" here means saving will accept it.
        $current = $request->filled('current') ? $request->user()->bioPages()->where('slug', $request->input('current'))->first() : null;
        $slug = strtolower((string) $request->input('slug'));
        $validator = validator(['slug' => $slug], ['slug' => $this->slugRules($current)]);

        return response()->json(['available' => $validator->passes(), 'message' => $validator->errors()->first('slug')]);
    }

    /**
     * Validation for a bio page slug; $current is the page being renamed, if any.
     * Members need at least `shortener.bio_slug_min_length` characters; LinkHop's
     * own admin accounts may keep shorter brand slugs. Existing short slugs stay valid.
     */
    private function slugRules(?BioPage $current = null): array
    {
        $min = auth()->user()?->isAdmin() ? 3 : (int) config('bio.slug_min_length', 5);

        return [
            'required',
            'string',
            'max:20',
            'regex:/^[a-z0-9]+$/',
            fn ($attribute, $value, $fail) => $value !== $current?->slug && strlen((string) $value) < $min
                ? $fail("The slug must be at least {$min} letters or numbers.") : null,
            Rule::unique('bio_pages', 'slug')->ignore($current?->id),
            // Reserved words are refused for new slugs; an existing slug is left alone.
            ReservedSlugs::rule($current?->slug),
        ];
    }

    private function handleQrImage($settings, $oldSettings = null)
    {
        if (isset($settings['image']) && str_starts_with($settings['image'], 'data:image')) {
            $base64Image = $settings['image'];
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $data = substr($base64Image, strpos($base64Image, ',') + 1);
                $extension = strtolower($type[1]);
                $data = base64_decode($data, true);
                // Only store real images up to 1 MB.
                if (in_array($extension, ['jpg', 'jpeg', 'gif', 'png']) && $data !== false && strlen($data) <= 1048576 && @getimagesizefromstring($data)) {
                    $filename = 'qr-logos/'.uniqid().'.'.$extension;
                    Storage::disk('public')->put($filename, $data);
                    $settings['image'] = '/storage/'.$filename;
                }
            }
        }

        return $settings;
    }

    /**
     * Delete an uploaded bio file once nothing references it any more. Paths can
     * come from user input or be shared by duplicated pages, so never delete a
     * file another page or block still uses.
     */
    /**
     * Every uploaded file a page and its blocks point at.
     *
     * @return array<int, string|null>
     */
    /**
     * Reject abusive page text and unsafe social link values.
     */
    private function assertSafeContent(array $data): void
    {
        $safety = app(UrlSafety::class);
        $errors = [];
        $text = implode(' ', array_filter([$data['slug'] ?? null, $data['title'] ?? null, $data['bio'] ?? null, $data['seo_title'] ?? null, $data['seo_description'] ?? null]));
        if ($text !== '' && array_intersect($safety->assess(null, $text)['signals'], config('safety.block_categories', [])) !== []) {
            $errors['title'] = 'This content is not allowed due to our security policy.';
        }
        foreach ((array) ($data['social_links'] ?? []) as $i => $social) {
            $value = trim((string) ($social['value'] ?? ''));
            if ($value === '') {
                continue;
            }
            // Check the URL the page will actually link to: some platforms turn a bare
            // "host.tld/path" handle into https://host.tld/path.
            $url = SocialUrlHelper::buildUrl((string) ($social['platform'] ?? ''), $value);
            if ($safety->scheme($url) !== null && ($reason = $safety->rejectionReason($url, '', UrlSafety::BIO_SCHEMES))) {
                $errors["social_links.{$i}.value"] = $reason;
            }
        }
        // An uploaded background is a path on our own disk (bio-images/…); only an outside address is checked.
        foreach (['background_image', 'cover_image'] as $field) {
            if (! empty($data[$field]) && ! str_starts_with($data[$field], '/')
                && ! preg_match('#^bio-images/[A-Za-z0-9._-]+$#', $data[$field])
                && ($reason = $safety->rejectionReason($data[$field]))) {
                $errors[$field] = $reason;
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function store(Request $request)
    {
        if (auth()->user()->bioPages()->count() >= self::MAX_PAGES) {
            return back()->withErrors(['slug' => 'You have reached the maximum limit of '.self::MAX_PAGES.' bio pages.']);
        }
        $request->merge(['slug' => strtolower($request->slug)]);
        $validated = $request->validate([
            'slug' => $this->slugRules(),
            'title' => 'required|string|max:'.BioPage::TITLE_MAX,
            'bio' => 'nullable|string|max:'.BioPage::BIO_MAX,
            'qr_settings' => 'nullable|json',
            'content_warning' => 'nullable|in:none,sensitive,adult',
            'warning_reason' => 'nullable|string|max:255',
        ]);
        $this->assertSafeContent($validated);
        $qrSettings = $request->filled('qr_settings') ? json_decode($request->qr_settings, true) : null;
        if ($qrSettings) {
            $qrSettings = $this->handleQrImage($qrSettings);
        }
        $bioPage = auth()->user()->bioPages()->create([
            'slug' => $validated['slug'],
            'title' => $validated['title'],
            'bio' => $validated['bio'] ?? null,
            'content_warning' => $validated['content_warning'] ?? 'none',
            'warning_reason' => $validated['warning_reason'] ?? null,
            'is_adult_content' => ($validated['content_warning'] ?? 'none') === 'adult',
            'theme' => 'default',
            'qr_settings' => $qrSettings,
        ]);

        return redirect()->route('bio.edit', $bioPage)->with('success', 'Bio page created successfully!');
    }

    public function edit(BioPage $bioPage)
    {
        $this->authorize('update', $bioPage);
        $bioPage->load(['links' => function ($query) {
            $query->orderBy('order');
        }]);
        $brands = config('brands.platforms', []);

        // Use the new editor view
        return view('bio::editor.index', compact('bioPage', 'brands'));
    }

    public function update(Request $request, BioPage $bioPage)
    {
        $this->authorize('update', $bioPage);
        // Two editor tabs: the later save must not silently overwrite the earlier one (L2).
        $base = $request->input('base_updated_at');
        if (is_string($base) && $base !== '' && $bioPage->updated_at
            && strtotime($base) !== $bioPage->updated_at->getTimestamp()) {
            return response()->json([
                'message' => 'This page was changed in another tab or window. Reload the editor to get the latest version.',
                'conflict' => true,
            ], 409);
        }
        if ($request->has('slug')) {
            $request->merge(['slug' => strtolower($request->slug)]);
        }
        $validated = $request->validate([
            'slug' => ['sometimes', ...$this->slugRules($bioPage)],
            'title' => 'sometimes|required|string|max:'.BioPage::TITLE_MAX,
            'bio' => 'nullable|string|max:'.BioPage::BIO_MAX,
            'theme' => CssValue::keyword(50),
            'layout' => CssValue::keyword(50),
            'header_layout' => 'nullable|in:'.implode(',', BioPage::HEADER_LAYOUTS),
            'cover_image' => ['nullable', 'string', 'max:500', 'regex:'.CssValue::IMAGE],
            'background_type' => 'nullable|in:color,gradient,image,solid',
            'background_value' => ['nullable', 'string', 'max:500', fn ($attribute, $value, $fail) => CssValue::isBackground($value) ? null : $fail('Use a colour, a gradient or an uploaded image.')],
            'background_color' => CssValue::color(20),
            'background_gradient' => ['nullable', 'string', 'max:255', 'regex:'.CssValue::GRADIENT],
            'background_image' => ['nullable', 'string', 'max:500', 'regex:'.CssValue::IMAGE],
            'text_color' => CssValue::color(50),
            'title_color' => CssValue::color(50),
            'bio_color' => CssValue::color(50),
            'link_bg_color' => CssValue::color(100),
            'link_text_color' => CssValue::color(50),
            'button_bg_color' => CssValue::color(100),
            'button_text_color' => CssValue::color(50),
            'button_shape' => CssValue::keyword(50),
            'button_shadow' => CssValue::keyword(50),
            'header_bg_color' => ['nullable', 'string', 'max:50', fn ($attribute, $value, $fail) => preg_match(CssValue::COLOR, (string) $value) || preg_match(CssValue::GRADIENT, (string) $value) ? null : $fail('Use a colour or a gradient.')],
            'font_family' => CssValue::keyword(50),
            'block_shape' => CssValue::keyword(20),
            'block_shadow' => CssValue::keyword(20),
            'social_icon_color' => CssValue::color(20),
            'social_placement' => 'nullable|in:top,bottom',
            'social_links' => 'nullable|array',
            'social_links.*' => 'nullable|array',
            'social_links.*.platform' => 'nullable|string',
            'social_links.*.value' => 'nullable|string',
            'social_links.*.enabled' => 'nullable|boolean',
            'socials' => 'nullable|array',
            'socials_position' => 'nullable|in:top,bottom',
            'social_icons_position' => 'nullable|in:below_bio,bottom_page',
            'avatar_shape' => 'nullable|in:circle,rounded,square',
            'badge' => 'nullable|in:verified,star,crown,fire',
            'badge_color' => CssValue::color(20),
            'seo_title' => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
            'seo_noindex' => 'nullable|boolean',
            'custom_css' => 'nullable|string',
            'is_published' => 'nullable|boolean',
            'is_adult_content' => 'nullable|boolean',
            'content_warning' => 'nullable|in:none,sensitive,adult',
            'warning_reason' => 'nullable|string|max:255',
            'is_public' => 'nullable|boolean',
            'show_in_directory' => 'nullable|boolean',
            'hide_branding' => 'nullable|boolean',
            'google_analytics_id' => 'nullable|string|max:50',
            'facebook_pixel_id' => 'nullable|string|max:50',
            'tiktok_pixel_id' => ['nullable', 'regex:/^[A-Z0-9]{20,30}$/'],
            'avatar' => 'nullable|image|max:2048',
            'avatar_url' => 'nullable|string|max:255',
            'password_enabled' => 'nullable|boolean',
            'password' => 'nullable|string|max:50',
            'allow_indexing' => 'nullable|boolean',
            'qr_settings' => 'nullable|array',
            'hover_effect' => CssValue::keyword(30),
            'background_animation' => CssValue::keyword(30),
        ]);
        $this->assertSafeContent($validated);
        if (! empty($validated['is_published']) && ! $bioPage->is_published && AbuseFlag::locks('bio_page', $bioPage->id)) {
            throw ValidationException::withMessages(['is_published' => 'This page was disabled by moderation. Contact support to appeal.']);
        }
        // The avatar path is set by uploads only; clients may clear it but not point it elsewhere.
        if (array_key_exists('avatar_url', $validated) && $validated['avatar_url'] !== null && $validated['avatar_url'] !== $bioPage->avatar_url) {
            unset($validated['avatar_url']);
        }
        $oldFiles = [$bioPage->avatar_url, $bioPage->background_image, $bioPage->cover_image, $bioPage->qr_settings['image'] ?? null, $bioPage->qr_settings['logo_url'] ?? null];
        if ($request->hasFile('avatar')) {
            $validated['avatar_url'] = $request->file('avatar')->store('avatars', 'public');
        }
        // socials is cast to array on the model; encoding it here stored a JSON string inside JSON (L1).
        if (isset($validated['qr_settings'])) {
            $validated['qr_settings'] = $this->handleQrImage($validated['qr_settings'], $bioPage->qr_settings);
        }
        // Ensure theme is not null (database doesn't allow null)
        if (array_key_exists('theme', $validated) && $validated['theme'] === null) {
            $validated['theme'] = 'default';
        }
        // Hash bio-page password on write; never store plaintext. An empty
        // submission leaves the existing password untouched.
        if (array_key_exists('password', $validated)) {
            if (filled($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }
        }
        if (array_key_exists('content_warning', $validated)) {
            $validated['is_adult_content'] = ($validated['content_warning'] === 'adult');
        } elseif (array_key_exists('is_adult_content', $validated)) {
            $validated['content_warning'] = $validated['is_adult_content'] ? 'adult' : 'none';
        }
        $bioPage->update($validated);
        BioUploads::deleteUnused($oldFiles);
        // Return JSON for AJAX requests
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Bio page updated successfully!',
                'bioPage' => $bioPage->fresh(),
            ]);
        }

        return back()->with('success', 'Bio page updated successfully!');
    }

    public function destroy(BioPage $bioPage)
    {
        $this->authorize('delete', $bioPage);
        $files = BioUploads::ofPage($bioPage);
        $bioPage->delete();
        BioUploads::deleteUnused($files);

        return redirect()->route('bio.index')->with('success', 'Bio page deleted successfully!');
    }

    public function analytics(BioPage $bioPage)
    {
        $this->authorize('view', $bioPage);
        // Only the ranges the page offers; click logs are kept 30 days (privacy policy).
        $days = (int) request('days', 30);
        $days = in_array($days, self::ANALYTICS_RANGES, true) ? $days : 30;
        $clicksData = BioClick::query()
            ->whereIn('bio_link_id', $bioPage->links->pluck('id'))
            ->where('clicked_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(clicked_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        $viewsChartLabels = [];
        $viewsChartData = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $viewsChartLabels[] = now()->subDays($i)->format('M d');
            $clickCount = $clicksData->firstWhere('date', $date);
            $viewsChartData[] = $clickCount ? $clickCount->count : 0;
        }
        // reorder(): the relation sorts by position, which would win over click_count.
        $topLinks = $bioPage->links()
            ->reorder()
            ->where('is_active', true)
            ->orderByDesc('click_count')
            ->limit(10)
            ->get();
        $countriesData = BioClick::query()
            ->whereIn('bio_link_id', $bioPage->links->pluck('id'))
            ->where('clicked_at', '>=', now()->subDays($days))
            ->whereNotNull('country')
            ->selectRaw('country, COUNT(*) as clicks')
            ->groupBy('country')
            ->orderByDesc('clicks')
            ->limit(10)
            ->get();
        $totalCountryClicks = $countriesData->sum('clicks');
        $topCountries = $countriesData->map(function ($item) use ($totalCountryClicks) {
            $item->percentage = $totalCountryClicks > 0 ? round(($item->clicks / $totalCountryClicks) * 100, 1) : 0;
            $item->country_name = $item->country;
            $item->flag = $this->getCountryFlag($item->country);

            return $item;
        });
        $devicesData = BioClick::query()
            ->whereIn('bio_link_id', $bioPage->links->pluck('id'))
            ->where('clicked_at', '>=', now()->subDays($days))
            ->whereNotNull('device')
            ->selectRaw('device, COUNT(*) as count')
            ->groupBy('device')
            ->orderByDesc('count')
            ->get();
        $deviceChartLabels = $devicesData->pluck('device')->map(function ($device) {
            return ucfirst($device);
        })->toArray();
        $deviceChartData = $devicesData->pluck('count')->toArray();
        $recentClicks = BioClick::query()
            ->with('bioLink')
            ->whereIn('bio_link_id', $bioPage->links->pluck('id'))
            ->orderByDesc('clicked_at')
            ->limit(20)
            ->get();
        $totalClicks = $bioPage->getTotalClicks();
        $ctr = $bioPage->view_count > 0 ? round(($totalClicks / $bioPage->view_count) * 100, 2) : 0;
        $stats = [
            'totalViews' => $bioPage->view_count,
            'totalClicks' => $totalClicks,
            'ctr' => $ctr,
            'activeLinks' => $bioPage->links()->where('is_active', true)->count(),
            'viewsChart' => [
                'labels' => $viewsChartLabels,
                'data' => $viewsChartData,
            ],
            'deviceChart' => [
                'labels' => $deviceChartLabels,
                'data' => $deviceChartData,
            ],
            'topLinks' => $topLinks,
            'topCountries' => $topCountries,
            'recentClicks' => $recentClicks,
        ];

        return view('bio::analytics', compact('bioPage', 'stats', 'days'));
    }

    private function getCountryFlag($countryCode)
    {
        $flags = [
            'US' => '🇺🇸', 'ID' => '🇮🇩', 'GB' => '🇬🇧', 'CA' => '🇨🇦', 'AU' => '🇦🇺',
            'DE' => '🇩🇪', 'FR' => '🇫🇷', 'JP' => '🇯🇵', 'CN' => '🇨🇳', 'IN' => '🇮🇳',
            'SG' => '🇸🇬', 'MY' => '🇲🇾', 'TH' => '🇹🇭', 'PH' => '🇵🇭', 'VN' => '🇻🇳',
        ];

        return $flags[$countryCode] ?? '🌍';
    }

    public function duplicate(BioPage $bioPage)
    {
        $this->authorize('view', $bioPage);
        if (auth()->user()->bioPages()->count() >= self::MAX_PAGES) {
            return back()->withErrors(['slug' => 'You have reached the maximum limit of '.self::MAX_PAGES.' bio pages.']);
        }
        // Slugs are alphanumeric only (max 20), so no "-copy" suffix.
        // Trim the base so the suffix always fits; truncating the suffix instead
        // repeated the same slug forever for long slugs.
        $counter = 1;
        do {
            $suffix = 'copy'.($counter > 1 ? $counter : '');
            $newSlug = substr($bioPage->slug, 0, 20 - strlen($suffix)).$suffix;
            $counter++;
        } while (BioPage::where('slug', $newSlug)->exists());
        $newBioPage = $bioPage->replicate(['view_count']);
        $newBioPage->slug = $newSlug;
        $newBioPage->title = $bioPage->title.' (Copy)';
        $newBioPage->save();
        foreach ($bioPage->links as $link) {
            $newLink = $link->replicate(['click_count', 'public_id']);
            $newLink->bio_page_id = $newBioPage->id;
            $newLink->save();
        }

        return redirect()->route('bio.edit', $newBioPage)->with('success', 'Bio page duplicated successfully!');
    }

    /**
     * Upload image for bio page blocks
     */
    public function uploadImage(Request $request, BioPage $bioPage)
    {
        $this->authorize('update', $bioPage);
        $request->validate([
            'image' => 'required|image|max:5120', // 5MB max
        ]);
        try {
            $path = $request->file('image')->store('bio-images', 'public');

            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => '/storage/'.$path,
                'image_url' => '/storage/'.$path,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload avatar for bio page
     */
    public function uploadAvatar(Request $request, BioPage $bioPage)
    {
        $this->authorize('update', $bioPage);
        $request->validate([
            'avatar' => 'required|image|max:2048', // 2MB max
        ]);
        try {
            $oldAvatar = $bioPage->avatar_url;
            $path = $request->file('avatar')->store('avatars', 'public');
            $bioPage->update(['avatar_url' => $path]);
            BioUploads::deleteIfUnused($oldAvatar);

            return response()->json([
                'success' => true,
                'avatar_url' => $path, // Return just the path
                'full_url' => '/storage/'.$path,
                'page_updated_at' => $bioPage->updated_at->toJSON(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload avatar: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload thumbnail for bio link block
     */
    public function uploadThumbnail(Request $request, BioPage $bioPage)
    {
        $this->authorize('update', $bioPage);
        try {
            $path = $this->storeImageOrSvg($request, 'thumbnail', 'bio-thumbnails');

            return response()->json([
                'success' => true,
                'thumbnail_url' => '/storage/'.$path,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload thumbnail: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stores an uploaded icon, logo or thumbnail. PNG, JPG, GIF and WebP as before, and now SVG too: Laravel's `image`
     * rule refuses SVG (it can carry script), which is why SVG icons always failed. An SVG is rebuilt by SvgSanitizer
     * and stored as plain markup; anything it cannot make safe is refused with a message the editor shows.
     */
    private function storeImageOrSvg(Request $request, string $field, string $directory): string
    {
        $file = $request->file($field);
        if ($file && $file->isValid() && (strtolower($file->getClientOriginalExtension()) === 'svg' || $file->getClientMimeType() === 'image/svg+xml')) {
            $svg = $file->getSize() <= SvgSanitizer::MAX_BYTES ? SvgSanitizer::clean((string) file_get_contents($file->getRealPath())) : null;
            if ($svg === null) {
                throw ValidationException::withMessages([$field => 'This SVG cannot be used. Use a plain SVG without scripts or external files, up to 1 MB.']);
            }
            $path = $directory.'/'.Str::random(40).'.svg';
            Storage::disk('public')->put($path, $svg);

            return $path;
        }
        $request->validate([$field => 'required|image|max:1024']);

        return $request->file($field)->store($directory, 'public');
    }

    /**
     * Upload custom icon for bio link block
     */
    public function uploadIcon(Request $request, BioPage $bioPage)
    {
        $this->authorize('update', $bioPage);
        try {
            $path = $this->storeImageOrSvg($request, 'icon', 'bio-icons');

            return response()->json([
                'success' => true,
                'icon_url' => '/storage/'.$path,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload icon: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload QR code logo
     */
    public function uploadQrLogo(Request $request, BioPage $bioPage)
    {
        $this->authorize('update', $bioPage);
        try {
            $oldLogo = $bioPage->qr_settings['logo_url'] ?? null;
            $path = $this->storeImageOrSvg($request, 'logo', 'qr-logos');
            $qrSettings = $bioPage->qr_settings ?? [];
            $qrSettings['logo_url'] = '/storage/'.$path;
            $bioPage->update(['qr_settings' => $qrSettings]);
            BioUploads::deleteIfUnused($oldLogo);

            return response()->json([
                'success' => true,
                'logo_url' => '/storage/'.$path,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload QR logo: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview bio page (for iframe in editor)
     */
    public function preview(BioPage $bioPage)
    {
        $this->authorize('view', $bioPage);
        $bioPage->load(['links' => function ($query) {
            $query->orderBy('order');
        }]);

        return view('bio::editor.preview', compact('bioPage'));
    }
}
