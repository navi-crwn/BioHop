<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\VerifyHubRequest;
use App\Models\AbuseFlag;
use App\Models\HopApp;
use App\Models\HopAudit;
use App\Models\User;
use App\Support\HopSignature;
use Carbon\CarbonImmutable;
use Hop\Bio\Models\BioClick;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Support\BioUploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HOP App Protocol v1 endpoints for BioHop: health, manifest, stats, content
 * moderation (pages and blocks), abuse reports/flags, settings, search, audit,
 * and account lifecycle events.
 */
class HopBioController extends Controller
{
    /** GET /api/hop/health */
    public function health(): JsonResponse
    {
        $db = 'ok';
        try {
            DB::select('SELECT 1');
        } catch (\Throwable) {
            $db = 'down';
        }
        $beat = Cache::get('queue:heartbeat');
        $beatAt = is_array($beat) ? ($beat['at'] ?? null) : (is_string($beat) ? $beat : null);
        $fresh = false;
        if ($beatAt) {
            try {
                $fresh = CarbonImmutable::parse($beatAt)->greaterThan(CarbonImmutable::now('UTC')->subMinutes(5));
            } catch (\Throwable) {
                $fresh = false;
            }
        }

        return response()->json([
            'ok' => $db === 'ok',
            'data' => [
                'app' => 'biohop',
                'version' => '1',
                'status' => $db === 'ok' ? 'ok' : 'down',
                'db' => $db,
                'queue' => $fresh ? 'ok' : 'stale',
                'queue_heartbeat_at' => $beatAt,
                'time' => CarbonImmutable::now('UTC')->toIso8601String(),
            ],
        ], $db === 'ok' ? 200 : 503);
    }

    /** GET /api/hop/manifest */
    public function manifest(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => [
                'app' => 'biohop',
                'name' => config('app.name', 'BioHop'),
                'version' => '1.0',
                'protocol' => 'hop-1',
                'icon' => 'id-card',
                'base_url' => config('app.url'),
                'capabilities' => ['admin_api', 'events', 'metrics', 'reports', 'settings', 'search'],
                'content_types' => [
                    [
                        'type' => 'page',
                        'label' => 'Bio Pages',
                        'actions' => ['unpublish', 'publish', 'delete', 'transfer', 'clear_custom_css', 'mark_nsfw', 'unmark_nsfw', 'mark_sensitive'],
                    ],
                    [
                        'type' => 'block',
                        'label' => 'Bio Blocks',
                        'actions' => ['disable', 'enable', 'delete'],
                    ],
                ],
                'settings_schema' => [
                    ['key' => 'bio.slug_min_length', 'type' => 'int', 'restart_required' => false],
                    ['key' => 'bio.max_pages_per_user', 'type' => 'int', 'restart_required' => false],
                    ['key' => 'bio.max_blocks_per_page', 'type' => 'int', 'restart_required' => false],
                    ['key' => 'bio.allow_custom_css', 'type' => 'bool', 'restart_required' => false],
                ],
            ],
        ]);
    }

    /** GET /api/hop/stats */
    public function stats(Request $request): JsonResponse
    {
        $now = CarbonImmutable::now('UTC');
        $until = ($this->day($request->query('until')) ?? $now)->endOfDay();
        $since = ($this->day($request->query('since')) ?? $until->subDays(29))->startOfDay();
        if ($since->greaterThan($until)) {
            [$since, $until] = [$until->startOfDay(), $since->endOfDay()];
        }
        if ($until->diffInDays($since) > 366) {
            $since = $until->subDays(366)->startOfDay();
        }

        $pagesTotal = BioPage::count();
        $pagesPub = BioPage::where('is_published', true)->count();
        $pagesDraft = BioPage::where('is_published', false)->count();
        $pagesAdult = BioPage::where('is_adult_content', true)->count();
        $blocksTotal = BioLink::count();
        $viewsTotal = (int) BioPage::sum('view_count');
        $clicksTotal = (int) BioLink::sum('click_count');
        $reportsOpen = Schema::hasTable('abuse_flags')
            ? AbuseFlag::whereIn('subject_type', ['bio_page', 'bio_link'])->where('status', AbuseFlag::STATUS_OPEN)->count()
            : 0;

        $totals = [
            'pages' => $pagesTotal,
            'pages_published' => $pagesPub,
            'pages_draft' => $pagesDraft,
            'pages_adult' => $pagesAdult,
            'blocks' => $blocksTotal,
            'views_total' => $viewsTotal,
            'clicks_total' => $clicksTotal,
            'reports_open' => $reportsOpen,
        ];

        return response()->json([
            'ok' => true,
            'data' => [
                'range' => ['since' => $since->toDateString(), 'until' => $until->toDateString()],
                'kpi' => $totals,
                'totals' => $totals,
                'period' => [
                    'pages_created' => BioPage::whereBetween('created_at', [$since, $until])->count(),
                    'views' => Schema::hasTable('bio_analytics') ? DB::table('bio_analytics')->where('event_type', 'view')->whereBetween('created_at', [$since, $until])->count() : 0,
                    'clicks' => Schema::hasTable('bio_analytics') ? DB::table('bio_analytics')->where('event_type', 'click')->whereBetween('created_at', [$since, $until])->count() : 0,
                ],
                'series' => [
                    'pages_created' => $this->daily('bio_pages', 'created_at', $since, $until),
                    'views' => Schema::hasTable('bio_analytics') ? $this->dailyAnalytics('view', $since, $until) : [],
                    'clicks' => Schema::hasTable('bio_analytics') ? $this->dailyAnalytics('click', $since, $until) : [],
                ],
            ],
        ]);
    }

    /** GET /api/hop/content?type=page|bio|block&q=&owner=&status=&cursor=&limit= */
    public function index(Request $request): JsonResponse
    {
        $rawType = (string) $request->query('type', 'page');
        $type = $rawType === 'bio' ? 'page' : $rawType;
        $limit = min(200, max(1, (int) $request->query('limit', 50)));
        $cursor = (int) $request->query('cursor', 0);
        $q = trim((string) $request->query('q', ''));
        $owner = trim((string) $request->query('owner', ''));
        $status = (string) $request->query('status', '');

        if ($type === 'page') {
            $query = BioPage::query()->with('user:id,username,name')->withCount('links')->orderByDesc('id');
            if ($cursor > 0) {
                $query->where('id', '<', $cursor);
            }
            if ($q !== '') {
                $query->where(fn ($w) => $w->where('slug', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%"));
            }
            if ($owner !== '' && $owner !== 'guest') {
                if (ctype_digit($owner)) {
                    $query->where('user_id', (int) $owner);
                } else {
                    $query->whereHas('user', fn ($u) => $u->where('username', $owner));
                }
            }
            match ($status) {
                'published' => $query->where('is_published', true),
                'draft' => $query->where('is_published', false),
                'adult' => $query->where('is_adult_content', true),
                'flagged' => Schema::hasTable('abuse_flags')
                    ? $query->whereIn('id', AbuseFlag::where('subject_type', 'bio_page')->where('status', '!=', AbuseFlag::STATUS_DISMISSED)->select('subject_id'))
                    : null,
                default => null,
            };
            $total = (clone $query)->reorder()->count();
            $rows = $query->limit($limit)->get();
            $data = $rows->map(fn (BioPage $p) => $this->pageRow($p, $rawType === 'bio' ? 'bio' : 'page'))->all();
        } elseif ($type === 'block') {
            $query = BioLink::query()->with(['bioPage:id,user_id,slug,title', 'bioPage.user:id,username,name'])->orderByDesc('id');
            if ($cursor > 0) {
                $query->where('id', '<', $cursor);
            }
            if ($q !== '') {
                $query->where(fn ($w) => $w->where('title', 'like', "%{$q}%")
                    ->orWhere('url', 'like', "%{$q}%")
                    ->orWhere('content', 'like', "%{$q}%"));
            }
            if ($owner !== '' && $owner !== 'guest') {
                $query->whereHas('bioPage', function ($p) use ($owner) {
                    if (ctype_digit($owner)) {
                        $p->where('user_id', (int) $owner);
                    } else {
                        $p->whereHas('user', fn ($u) => $u->where('username', $owner));
                    }
                });
            }
            match ($status) {
                'active' => $query->where('is_active', true),
                'disabled' => $query->where('is_active', false),
                default => null,
            };
            $total = (clone $query)->reorder()->count();
            $rows = $query->limit($limit)->get();
            $data = $rows->map(fn (BioLink $b) => $this->blockRow($b))->all();
        } else {
            return $this->fail('unknown_type', 'type must be page, bio or block', 422);
        }

        $next = $rows->count() === $limit ? (string) $rows->last()->id : null;

        return response()->json(['ok' => true, 'data' => $data, 'page' => ['next' => $next, 'total' => $total]]);
    }

    /** GET /api/hop/content/{type}/{id} */
    public function show(string $type, int $id): JsonResponse
    {
        $normType = $type === 'bio' ? 'page' : $type;
        if ($normType === 'page') {
            $p = BioPage::with('user:id,username,name')->withCount('links')->find($id);
            if (! $p) {
                return $this->fail('not_found', 'no such bio page', 404);
            }
            $row = $this->pageRow($p, $type === 'bio' ? 'bio' : 'page') + [
                'description' => $p->description,
                'theme' => $p->theme,
                'custom_css' => $p->custom_css,
                'adult' => (bool) $p->is_adult_content,
                'content_warning' => $p->content_warning ?? ($p->is_adult_content ? 'adult' : 'none'),
                'warning_reason' => $p->warning_reason,
                'password' => (bool) $p->password_enabled,
                'seo_noindex' => (bool) $p->seo_noindex,
                'blocks' => BioLink::where('bio_page_id', $p->id)
                    ->orderBy('order')
                    ->limit(100)
                    ->get(['id', 'title', 'url', 'type', 'is_active', 'click_count', 'order as position'])
                    ->all(),
                'findings' => $this->flagsFor('bio_page', $p->id),
            ];

            return response()->json(['ok' => true, 'data' => $row]);
        }
        if ($normType === 'block') {
            $b = BioLink::with(['bioPage:id,user_id,slug,title', 'bioPage.user:id,username,name'])->find($id);
            if (! $b) {
                return $this->fail('not_found', 'no such bio block', 404);
            }
            $row = $this->blockRow($b) + [
                'content' => $b->content,
                'embed_type' => $b->embed_type,
                'embed_url' => $b->embed_url,
                'thumbnail_url' => $b->thumbnail_url,
                'custom_icon' => $b->custom_icon,
                'findings' => $this->flagsFor('bio_link', $b->id),
            ];

            return response()->json(['ok' => true, 'data' => $row]);
        }

        return $this->fail('unknown_type', 'type must be page, bio or block', 422);
    }

    /** POST /api/hop/content/{type}/{id}/actions */
    public function act(Request $request, string $type, int $id): JsonResponse
    {
        $normType = $type === 'bio' ? 'page' : $type;
        $action = (string) $request->input('action', '');
        $params = (array) $request->input('params', []);
        $audit = $this->auditBase($request, $action, $normType, (string) $id, $request->input('reason'));
        $result = [];

        if ($normType === 'page') {
            $p = BioPage::with('user')->find($id);
            if (! $p) {
                return $this->fail('not_found', 'no such bio page', 404);
            }
            if ($p->user && $p->user->isOwner() && in_array($action, ['unpublish', 'delete'], true)) {
                return $this->fail('owner_protected', "The owner's pages are not moderated through the API.", 403);
            }
            switch ($action) {
                case 'unpublish':
                    $p->forceFill(['is_published' => false])->save();
                    if (Schema::hasTable('abuse_flags')) {
                        AbuseFlag::firstOrCreate(
                            ['subject_type' => 'bio_page', 'subject_id' => $p->id, 'action' => AbuseFlag::ACTION_DISABLED, 'status' => AbuseFlag::STATUS_CONFIRMED],
                            ['user_id' => $p->user_id, 'score' => 100, 'signals' => [], 'summary' => 'Unpublished by an admin']
                        );
                    }
                    break;
                case 'publish':
                    $p->forceFill(['is_published' => true])->save();
                    if (Schema::hasTable('abuse_flags')) {
                        AbuseFlag::where('subject_type', 'bio_page')->where('subject_id', $p->id)->update(['status' => AbuseFlag::STATUS_DISMISSED]);
                    }
                    break;
                case 'delete':
                    $files = BioUploads::ofPage($p);
                    $p->delete();
                    BioUploads::deleteUnused($files);
                    $this->recordAudit($audit + ['params' => ['slug' => $p->slug, 'title' => $p->title]]);

                    return response()->json(['ok' => true, 'data' => ['deleted' => true, 'id' => (string) $id]]);
                case 'transfer':
                    $to = User::find((int) ($params['to'] ?? 0));
                    if (! $to) {
                        return $this->fail('bad_params', 'params.to must be a HOP id', 422);
                    }
                    $p->forceFill(['user_id' => $to->id])->save();
                    $result = ['to' => (string) $to->id];
                    break;
                case 'clear_custom_css':
                    $p->forceFill(['custom_css' => null])->save();
                    break;
                case 'mark_nsfw':
                    $p->forceFill(['is_adult_content' => true, 'content_warning' => 'adult'])->save();
                    break;
                case 'unmark_nsfw':
                    $p->forceFill(['is_adult_content' => false, 'content_warning' => 'none'])->save();
                    break;
                case 'mark_sensitive':
                    $p->forceFill(['is_adult_content' => false, 'content_warning' => 'sensitive'])->save();
                    break;
                default:
                    return $this->fail('unknown_action', 'unpublish, publish, delete, transfer, clear_custom_css, mark_nsfw, unmark_nsfw or mark_sensitive', 422);
            }
            $this->recordAudit($audit + ['params' => $result ?: $params]);

            return response()->json(['ok' => true, 'data' => $this->pageRow($p->fresh('user')->loadCount('links'), $type === 'bio' ? 'bio' : 'page')]);
        }

        if ($normType === 'block') {
            $b = BioLink::with(['bioPage.user'])->find($id);
            if (! $b) {
                return $this->fail('not_found', 'no such bio block', 404);
            }
            if ($b->bioPage?->user && $b->bioPage->user->isOwner() && in_array($action, ['disable', 'delete'], true)) {
                return $this->fail('owner_protected', "The owner's blocks are not moderated through the API.", 403);
            }
            switch ($action) {
                case 'disable':
                    $b->forceFill(['is_active' => false])->save();
                    break;
                case 'enable':
                    $b->forceFill(['is_active' => true])->save();
                    if (Schema::hasTable('abuse_flags')) {
                        AbuseFlag::where('subject_type', 'bio_link')->where('subject_id', $b->id)->update(['status' => AbuseFlag::STATUS_DISMISSED]);
                    }
                    break;
                case 'delete':
                    $files = BioUploads::ofLink($b);
                    $b->delete();
                    BioUploads::deleteUnused($files);
                    $this->recordAudit($audit + ['params' => ['title' => $b->title, 'url' => $b->url]]);

                    return response()->json(['ok' => true, 'data' => ['deleted' => true, 'id' => (string) $id]]);
                default:
                    return $this->fail('unknown_action', 'disable, enable or delete', 422);
            }
            $this->recordAudit($audit + ['params' => $params]);

            return response()->json(['ok' => true, 'data' => $this->blockRow($b->fresh(['bioPage:id,user_id,slug,title', 'bioPage.user:id,username,name']))]);
        }

        return $this->fail('unknown_type', 'type must be page, bio or block', 422);
    }

    /** GET /api/hop/reports */
    public function reports(Request $request): JsonResponse
    {
        $status = (string) $request->query('status', 'open');
        $limit = min(200, max(1, (int) $request->query('limit', 50)));
        $flags = collect();
        if (Schema::hasTable('abuse_flags')) {
            $flags = AbuseFlag::query()->with('user:id,username')
                ->whereIn('subject_type', ['bio_page', 'bio_link'])
                ->latest('id')
                ->when($status === 'open', fn ($q) => $q->where('status', AbuseFlag::STATUS_OPEN))
                ->when($status === 'closed', fn ($q) => $q->where('status', '!=', AbuseFlag::STATUS_OPEN))
                ->limit($limit)
                ->get()
                ->map(fn (AbuseFlag $f) => $this->flagRow($f));
        }

        return response()->json(['ok' => true, 'data' => $flags->values()->all(), 'page' => ['next' => null, 'total' => $flags->count()]]);
    }

    /** POST /api/hop/reports/{kind}/{id}/actions and POST /api/hop/reports/{id}/actions */
    public function reportAct(Request $request, string $kindOrId, ?int $maybeId = null): JsonResponse
    {
        $kind = $maybeId === null ? 'flag' : $kindOrId;
        $id = $maybeId ?? (int) $kindOrId;
        $action = (string) $request->input('action', '');
        $audit = $this->auditBase($request, $action, $kind, (string) $id, $request->input('reason'));

        if ($kind === 'flag') {
            $f = AbuseFlag::find($id);
            if (! $f) {
                return $this->fail('not_found', 'no such finding', 404);
            }
            if ($action === 'confirm') {
                $f->update(['status' => AbuseFlag::STATUS_CONFIRMED]);
            } elseif (in_array($action, ['restore', 'dismiss', 'resolve'], true)) {
                if ($action === 'restore') {
                    match ($f->subject_type) {
                        'bio_page' => BioPage::whereKey($f->subject_id)->update(['is_published' => true]),
                        'bio_link' => BioLink::whereKey($f->subject_id)->update(['is_active' => true]),
                        default => null,
                    };
                }
                $f->update(['status' => AbuseFlag::STATUS_DISMISSED]);
            } else {
                return $this->fail('unknown_action', 'confirm, restore, dismiss or resolve', 422);
            }
            $this->recordAudit($audit);

            return response()->json(['ok' => true, 'data' => $this->flagRow($f->fresh('user'))]);
        }

        return $this->fail('unknown_type', 'kind must be flag', 422);
    }

    /** GET /api/hop/settings */
    public function settings(): JsonResponse
    {
        $stored = (array) Cache::get('biohop:settings', []);
        $rows = [
            [
                'key' => 'bio.slug_min_length',
                'type' => 'int',
                'value' => (int) ($stored['bio.slug_min_length'] ?? config('bio.slug_min_length', 3)),
                'default' => 3,
                'description' => 'Panjang minimum slug halaman bio',
                'restart_required' => false,
                'readonly' => false,
            ],
            [
                'key' => 'bio.max_pages_per_user',
                'type' => 'int',
                'value' => (int) ($stored['bio.max_pages_per_user'] ?? config('bio.max_pages_per_user', 5)),
                'default' => 5,
                'description' => 'Batas maksimum halaman bio per akun',
                'restart_required' => false,
                'readonly' => false,
            ],
            [
                'key' => 'bio.max_blocks_per_page',
                'type' => 'int',
                'value' => (int) ($stored['bio.max_blocks_per_page'] ?? config('bio.max_blocks_per_page', 50)),
                'default' => 50,
                'description' => 'Batas maksimum blok per halaman bio',
                'restart_required' => false,
                'readonly' => false,
            ],
            [
                'key' => 'bio.allow_custom_css',
                'type' => 'bool',
                'value' => (bool) ($stored['bio.allow_custom_css'] ?? config('bio.allow_custom_css', true)),
                'default' => true,
                'description' => 'Izinkan CSS kustom pada halaman bio',
                'restart_required' => false,
                'readonly' => false,
            ],
            [
                'key' => 'session_lifetime_min',
                'type' => 'int',
                'value' => (int) config('session.lifetime', 120),
                'default' => 120,
                'description' => '.env / config',
                'restart_required' => true,
                'readonly' => true,
            ],
        ];

        return response()->json(['ok' => true, 'data' => $rows]);
    }

    /** PUT /api/hop/settings */
    public function saveSettings(Request $request): JsonResponse
    {
        $allowed = ['bio.slug_min_length', 'bio.max_pages_per_user', 'bio.max_blocks_per_page', 'bio.allow_custom_css'];
        $stored = (array) Cache::get('biohop:settings', []);
        $changes = [];
        foreach ((array) $request->all() as $k => $v) {
            if (in_array($k, $allowed, true)) {
                $changes[$k] = $k === 'bio.allow_custom_css' ? (bool) $v : max(1, (int) $v);
                $stored[$k] = $changes[$k];
            }
        }
        if (! $changes) {
            return $this->fail('bad_params', 'nothing editable in the body', 422);
        }
        Cache::forever('biohop:settings', $stored);
        $this->recordAudit($this->auditBase($request, 'settings.update', 'settings', 'bio', null) + ['params' => $changes]);

        return $this->settings();
    }

    /** GET /api/hop/search?q=&limit= */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $limit = min(50, max(1, (int) $request->query('limit', 20)));
        if ($q === '') {
            return response()->json(['ok' => true, 'data' => []]);
        }
        $pages = BioPage::with('user:id,username,name')
            ->withCount('links')
            ->where(fn ($w) => $w->where('slug', 'like', "%{$q}%")->orWhere('title', 'like', "%{$q}%"))
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (BioPage $p) => $this->pageRow($p));

        return response()->json(['ok' => true, 'data' => $pages->values()->all()]);
    }

    /** GET /api/hop/audit?limit= */
    public function audit(Request $request): JsonResponse
    {
        $limit = min(200, max(1, (int) $request->query('limit', 50)));
        if (! Schema::hasTable('hop_audit')) {
            return response()->json(['ok' => true, 'data' => []]);
        }
        $rows = HopAudit::query()->orderByDesc('id')->limit($limit)->get()->map(fn (HopAudit $a) => $a->toApi())->all();

        return response()->json(['ok' => true, 'data' => $rows]);
    }

    /**
     * POST /hop/events and POST /api/hop/events: accepts only HOP-1 signed
     * requests from the hub (X-HOP-App: helink) or hub.signed requests
     * (X-Hop-Signature with the shared hub secret). Other registry apps are
     * rejected with 403.
     */
    public function events(Request $request): JsonResponse
    {
        $caller = $this->verifyEventCaller($request);
        if ($caller === 'forbidden') {
            return $this->fail('forbidden', 'only the hub may send account events', 403);
        }
        if ($caller !== 'ok') {
            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }
        $type = (string) $request->input('type', '');
        $data = (array) $request->input('data', []);
        if ($type === 'account.deleted') {
            $userId = (int) ($data['sub'] ?? $data['hop_id'] ?? 0);
            $this->purgeAccountFiles($userId, (array) ($data['bio_uploads'] ?? []));
        }

        return response()->json(['ok' => true]);
    }

    /** GET /internal/accounts/{user}/summary: a few counts for the hub dashboard card (numbers only, no content). */
    public function summary(int $user): JsonResponse
    {
        $pages = BioPage::where('user_id', $user);

        return response()->json(['ok' => true, 'app' => 'biohop', 'data' => [
            'pages' => (clone $pages)->count(),
            'published' => (clone $pages)->where('is_published', true)->count(),
            'clicks_7d' => BioClick::whereIn('bio_link_id', BioLink::whereIn('bio_page_id', (clone $pages)->select('id'))->select('id'))
                ->where('clicked_at', '>=', now()->subDays(7))->count(),
        ]]);
    }

    /** DELETE /internal/accounts/{user}: called by the hub on account deletion. */
    public function purge(int $user, Request $request): JsonResponse
    {
        $stats = $this->purgeAccountFiles($user, (array) $request->input('bio_uploads', []));

        return response()->json(['ok' => true, 'app' => 'biohop', 'data' => $stats]);
    }

    /**
     * @param  array<int, string>  $explicitUploads
     * @return array{bio_pages: int}
     */
    private function purgeAccountFiles(int $userId, array $explicitUploads = []): array
    {
        $files = $explicitUploads;
        $count = 0;
        if ($userId > 0) {
            $pages = BioPage::where('user_id', $userId)->get();
            $count = $pages->count();
            foreach ($pages as $page) {
                array_push($files, ...BioUploads::ofPage($page));
                $page->delete();
            }
        }
        BioUploads::deleteUnused(array_unique(array_filter($files)));

        return ['bio_pages' => $count];
    }

    private function verifyEventCaller(Request $request): string
    {
        $appHeader = (string) $request->header('X-HOP-App', '');
        if ($appHeader !== '') {
            if ($appHeader !== 'helink') {
                return HopSignature::verify($request) instanceof HopApp ? 'forbidden' : 'unauthorized';
            }
            if (HopSignature::verify($request) instanceof HopApp) {
                return 'ok';
            }
        }
        // The hub's own channel: the shared hub secret (hop-auth.secret / hop-auth.secret_prev), HOP-1 or legacy hub.signed.
        $secrets = array_values(array_filter([
            (string) config('hop-auth.secret'),
            (string) config('hop-auth.secret_prev'),
        ], fn ($s) => strlen($s) >= 32));

        if (empty($secrets)) {
            return 'unauthorized';
        }
        $ts = (string) $request->header('X-Hop-Timestamp', '');
        $sig = (string) $request->header('X-Hop-Signature', '');
        $maxAge = (int) config('hop.request_max_age', 120);
        if (! ctype_digit($ts) || abs(time() - (int) $ts) > $maxAge || $sig === '') {
            return 'unauthorized';
        }
        $nonce = (string) $request->header('X-Hop-Nonce', '');
        if ($nonce !== '') {
            if (strlen($nonce) < 16 || strlen($nonce) > 128) {
                return 'unauthorized';
            }
            $base = HopSignature::stringToSign('helink', $ts, $nonce, $request->method(), $request->getRequestUri(), (string) $request->getContent());
            $matched = false;
            foreach ($secrets as $s) {
                if (hash_equals(HopSignature::b64(hash_hmac('sha256', $base, $s, true)), $sig)) {
                    $matched = true;
                    break;
                }
            }
            if (! $matched) {
                return 'unauthorized';
            }

            return Cache::add('hop:nonce:helink:'.sha1($nonce), 1, $maxAge * 2) ? 'ok' : 'unauthorized';
        }
        $path = '/'.ltrim($request->path(), '/');
        $matched = false;
        foreach ($secrets as $s) {
            $expected = VerifyHubRequest::sign($s, (int) $ts, $request->method(), $path, (string) $request->getContent());
            if (hash_equals($expected, strtolower($sig))) {
                $matched = true;
                break;
            }
        }

        return $matched ? 'ok' : 'unauthorized';
    }

    private function pageRow(BioPage $p, string $type = 'page'): array
    {
        return [
            'id' => (string) $p->id,
            'type' => $type,
            'title' => $p->title,
            'slug' => $p->slug,
            'owner' => $p->user ? ['sub' => (string) $p->user->id, 'username' => $p->user->username, 'name' => $p->user->name] : null,
            'status' => $p->is_published ? 'published' : 'draft',
            'views' => (int) $p->view_count,
            'blocks_count' => (int) ($p->links_count ?? $p->links()->count()),
            'created_at' => $p->created_at?->toIso8601String(),
            'updated_at' => $p->updated_at?->toIso8601String(),
            'url' => public_url('b/'.$p->slug),
            'flags' => array_values(array_filter([
                $p->isAdult() ? 'adult' : ($p->isSensitive() ? 'sensitive' : null),
                $p->password_enabled ? 'password' : null,
                $p->seo_noindex ? 'noindex' : null,
                ! empty($p->custom_css) ? 'custom_css' : null,
            ])),
        ];
    }

    private function blockRow(BioLink $b): array
    {
        $page = $b->bioPage;
        $owner = $page?->user;

        return [
            'id' => (string) $b->id,
            'type' => 'block',
            'block_type' => $b->type,
            'title' => $b->title ?: ($b->type ?: 'block'),
            'url' => $b->url,
            'page' => $page ? ['id' => (string) $page->id, 'slug' => $page->slug, 'title' => $page->title] : null,
            'owner' => $owner ? ['sub' => (string) $owner->id, 'username' => $owner->username, 'name' => $owner->name] : null,
            'status' => $b->is_active ? 'active' : 'disabled',
            'clicks' => (int) $b->click_count,
            'position' => (int) $b->order,
            'created_at' => $b->created_at?->toIso8601String(),
        ];
    }

    private function flagRow(AbuseFlag $f): array
    {
        return [
            'kind' => 'flag',
            'id' => (string) $f->id,
            'subject' => ['type' => $f->subject_type, 'id' => (string) $f->subject_id],
            'owner' => $f->user ? ['sub' => (string) $f->user->id, 'username' => $f->user->username] : null,
            'score' => (int) $f->score,
            'action' => $f->action,
            'summary' => $f->summary,
            'status' => $f->status,
            'created_at' => $f->created_at?->toIso8601String(),
        ];
    }

    private function flagsFor(string $type, int $id): array
    {
        if (! Schema::hasTable('abuse_flags')) {
            return [];
        }

        return AbuseFlag::where('subject_type', $type)->where('subject_id', $id)->latest('id')->limit(10)->get()->map(fn ($f) => $this->flagRow($f))->all();
    }

    private function auditBase(Request $request, string $action, string $targetType, string $targetId, mixed $reason): array
    {
        /** @var HopApp|null $app */
        $app = $request->attributes->get('hop_app');

        return [
            'actor' => (string) $request->header('X-HOP-Actor', 'system'),
            'app_slug' => $app?->slug ?? 'imperium',
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'reason' => is_string($reason) ? mb_substr($reason, 0, 500) : null,
        ];
    }

    private function recordAudit(array $attributes): void
    {
        if (Schema::hasTable('hop_audit')) {
            HopAudit::create($attributes);
        }
    }

    /** @return list<array{0: string, 1: int}> */
    private function daily(string $table, string $column, CarbonImmutable $since, CarbonImmutable $until): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }
        $rows = DB::table($table)->selectRaw("DATE($column) as d, COUNT(*) as n")
            ->whereBetween($column, [$since, $until])->groupBy('d')->pluck('n', 'd');
        $out = [];
        for ($day = $since->startOfDay(); $day->lte($until); $day = $day->addDay()) {
            $out[] = [$day->toDateString(), (int) ($rows[$day->toDateString()] ?? 0)];
        }

        return $out;
    }

    /** @return list<array{0: string, 1: int}> */
    private function dailyAnalytics(string $eventType, CarbonImmutable $since, CarbonImmutable $until): array
    {
        $rows = DB::table('bio_analytics')->selectRaw('DATE(created_at) as d, COUNT(*) as n')
            ->where('event_type', $eventType)
            ->whereBetween('created_at', [$since, $until])
            ->groupBy('d')
            ->pluck('n', 'd');
        $out = [];
        for ($day = $since->startOfDay(); $day->lte($until); $day = $day->addDay()) {
            $out[] = [$day->toDateString(), (int) ($rows[$day->toDateString()] ?? 0)];
        }

        return $out;
    }

    private function day(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }
        try {
            return CarbonImmutable::parse($value, 'UTC');
        } catch (\Throwable) {
            return null;
        }
    }

    private function fail(string $error, string $message, int $status): JsonResponse
    {
        return response()->json(['ok' => false, 'error' => $error, 'message' => $message], $status);
    }
}
