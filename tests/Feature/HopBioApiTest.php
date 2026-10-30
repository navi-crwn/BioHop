<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyHubRequest;
use App\Jobs\QueueHeartbeat;
use App\Models\AbuseFlag;
use App\Models\HopApp;
use App\Models\User;
use App\Support\HopSignature;
use Carbon\CarbonImmutable;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HopBioApiTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'imperium-secret-that-is-long-enough-1234';

    private const HUB_SECRET = 'shared-hub-sso-secret-that-is-long-enough-99';

    protected function setUp(): void
    {
        parent::setUp();
        HopApp::factory()->imperium(self::SECRET)->create();
        config(['hop-auth.secret' => self::HUB_SECRET]);
    }

    private function signed(string $method, string $path, array $body = [], string $actor = '7', string $app = 'imperium', string $secret = self::SECRET)
    {
        $json = $body ? json_encode($body) : '';
        $ts = (string) time();
        $nonce = HopSignature::b64(random_bytes(16));
        $sig = HopSignature::b64(hash_hmac('sha256', HopSignature::stringToSign($app, $ts, $nonce, $method, $path, $json), $secret, true));

        return $this->call($method, $path, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_HOP_APP' => $app,
            'HTTP_X_HOP_TIMESTAMP' => $ts,
            'HTTP_X_HOP_NONCE' => $nonce,
            'HTTP_X_HOP_ACTOR' => $actor,
            'HTTP_X_HOP_SIGNATURE' => $sig,
        ], $json);
    }

    private function hubSigned(string $method, string $path, array $body = [])
    {
        $ts = time();
        $json = $body ? json_encode($body) : '';
        $sig = VerifyHubRequest::sign(self::HUB_SECRET, $ts, $method, $path, $json);

        return $this->call($method, $path, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_HOP_TIMESTAMP' => (string) $ts,
            'HTTP_X_HOP_SIGNATURE' => $sig,
        ], $json);
    }

    public function test_refuses_unsigned_or_non_admin_client_requests(): void
    {
        $this->getJson('/api/hop/manifest')->assertStatus(404);
        $this->getJson('/api/hop/manifest', ['X-HOP-App' => 'imperium'])->assertStatus(401);
        HopApp::factory()->pixelhop('pixelhop-secret-that-is-long-enough-123456')->create();
        $this->signed('GET', '/api/hop/manifest', app: 'pixelhop', secret: 'pixelhop-secret-that-is-long-enough-123456')
            ->assertStatus(403);
    }

    public function test_health_queue_status_reflects_heartbeat(): void
    {
        Cache::forget('queue:heartbeat');

        // Initially no heartbeat: queue is stale
        $this->signed('GET', '/api/hop/health')
            ->assertOk()
            ->assertJsonPath('data.queue', 'stale')
            ->assertJsonPath('data.queue_heartbeat_at', null);

        // Run queue heartbeat job
        (new QueueHeartbeat)->handle();

        $res = $this->signed('GET', '/api/hop/health')
            ->assertOk()
            ->assertJsonPath('data.queue', 'ok');

        $this->assertNotNull($res->json('data.queue_heartbeat_at'));

        // Simulate stale heartbeat (> 5 minutes ago)
        Cache::put('queue:heartbeat', [
            'at' => CarbonImmutable::now('UTC')->subMinutes(6)->toIso8601String(),
        ], now()->addMinutes(10));

        $this->signed('GET', '/api/hop/health')
            ->assertOk()
            ->assertJsonPath('data.queue', 'stale');
    }

    public function test_health_manifest_and_stats(): void
    {
        $u = User::factory()->create(['username' => 'rani']);
        $p1 = BioPage::create(['user_id' => $u->id, 'slug' => 'rani', 'title' => 'Rani Bio', 'is_published' => true, 'view_count' => 12]);
        BioPage::create(['user_id' => $u->id, 'slug' => 'draft', 'title' => 'Draft Bio', 'is_published' => false, 'is_adult_content' => true]);
        BioLink::create(['bio_page_id' => $p1->id, 'type' => 'link', 'title' => 'Site', 'url' => 'https://example.com', 'position' => 1, 'is_active' => true, 'click_count' => 4]);

        $this->signed('GET', '/api/hop/health')
            ->assertOk()
            ->assertJsonPath('data.app', 'biohop')
            ->assertJsonPath('data.db', 'ok');

        $this->signed('GET', '/api/hop/manifest')
            ->assertOk()
            ->assertJsonPath('data.app', 'biohop')
            ->assertJsonPath('data.content_types.0.type', 'page')
            ->assertJsonPath('data.content_types.1.type', 'block');

        $this->signed('GET', '/api/hop/stats')
            ->assertOk()
            ->assertJsonPath('data.kpi.pages', 2)
            ->assertJsonPath('data.totals.pages', 2)
            ->assertJsonPath('data.totals.pages_published', 1)
            ->assertJsonPath('data.totals.pages_draft', 1)
            ->assertJsonPath('data.totals.pages_adult', 1)
            ->assertJsonPath('data.totals.blocks', 1)
            ->assertJsonPath('data.totals.views_total', 12)
            ->assertJsonPath('data.totals.clicks_total', 4);
    }

    public function test_content_listing_detail_and_actions_for_pages_and_blocks(): void
    {
        $u = User::factory()->create(['username' => 'budi']);
        $owner = User::factory()->create(['username' => 'boss', 'role' => 'superadmin']);
        DB::table('roles')->insert(['user_id' => $owner->id, 'role' => 'owner', 'created_at' => now(), 'updated_at' => now()]);

        $page = BioPage::create([
            'user_id' => $u->id,
            'slug' => 'budi',
            'title' => 'Budi Page',
            'is_published' => true,
            'custom_css' => '.bio { color: red; }',
        ]);
        $ownerPage = BioPage::create([
            'user_id' => $owner->id,
            'slug' => 'boss',
            'title' => 'Boss Page',
            'is_published' => true,
        ]);
        $block = BioLink::create([
            'bio_page_id' => $page->id,
            'type' => 'link',
            'title' => 'My Portfolio',
            'url' => 'https://portfolio.example.com',
            'position' => 1,
            'is_active' => true,
        ]);

        // List pages (accepts both type=page and type=bio)
        $this->signed('GET', '/api/hop/content?type=page&status=published')
            ->assertOk()
            ->assertJsonPath('page.total', 2);
        $this->signed('GET', '/api/hop/content?type=bio&q=Budi')
            ->assertOk()
            ->assertJsonPath('page.total', 1)
            ->assertJsonPath('data.0.slug', 'budi');

        // List blocks
        $this->signed('GET', '/api/hop/content?type=block&q=Portfolio')
            ->assertOk()
            ->assertJsonPath('page.total', 1)
            ->assertJsonPath('data.0.title', 'My Portfolio');

        // Detail
        $this->signed('GET', "/api/hop/content/page/{$page->id}")
            ->assertOk()
            ->assertJsonPath('data.slug', 'budi')
            ->assertJsonPath('data.blocks.0.title', 'My Portfolio');
        $this->signed('GET', "/api/hop/content/block/{$block->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'My Portfolio');

        // Owner protection
        $this->signed('POST', "/api/hop/content/page/{$ownerPage->id}/actions", ['action' => 'unpublish', 'reason' => 'test'])
            ->assertStatus(403)
            ->assertJsonPath('error', 'owner_protected');

        // Page actions: unpublish, publish, clear_custom_css, mark_nsfw, unmark_nsfw, transfer
        $this->signed('POST', "/api/hop/content/page/{$page->id}/actions", ['action' => 'unpublish', 'reason' => 'spam'])
            ->assertOk()
            ->assertJsonPath('data.status', 'draft');
        $this->signed('POST', "/api/hop/content/page/{$page->id}/actions", ['action' => 'publish', 'reason' => 'ok'])
            ->assertOk()
            ->assertJsonPath('data.status', 'published');
        $this->signed('POST', "/api/hop/content/page/{$page->id}/actions", ['action' => 'clear_custom_css', 'reason' => 'broken css'])
            ->assertOk();
        $this->assertNull($page->fresh()->custom_css);
        $this->signed('POST', "/api/hop/content/page/{$page->id}/actions", ['action' => 'mark_nsfw', 'reason' => 'adult'])
            ->assertOk();
        $this->assertTrue((bool) $page->fresh()->is_adult_content);
        $this->signed('POST', "/api/hop/content/page/{$page->id}/actions", ['action' => 'unmark_nsfw', 'reason' => 'clean'])
            ->assertOk();
        $this->assertFalse((bool) $page->fresh()->is_adult_content);

        // Block actions: disable, enable, delete
        $this->signed('POST', "/api/hop/content/block/{$block->id}/actions", ['action' => 'disable', 'reason' => 'bad link'])
            ->assertOk()
            ->assertJsonPath('data.status', 'disabled');
        $this->signed('POST', "/api/hop/content/block/{$block->id}/actions", ['action' => 'enable', 'reason' => 'fixed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
        $this->signed('POST', "/api/hop/content/block/{$block->id}/actions", ['action' => 'delete', 'reason' => 'spam'])
            ->assertOk()
            ->assertJsonPath('data.deleted', true);
        $this->assertDatabaseMissing('bio_links', ['id' => $block->id]);
    }

    public function test_reports_settings_search_and_audit(): void
    {
        $u = User::factory()->create(['username' => 'siti']);
        $page = BioPage::create(['user_id' => $u->id, 'slug' => 'siti', 'title' => 'Siti Bio', 'is_published' => false]);
        $flag = AbuseFlag::create([
            'subject_type' => 'bio_page',
            'subject_id' => $page->id,
            'user_id' => $u->id,
            'score' => 80,
            'signals' => ['phishing'],
            'action' => 'disabled',
            'status' => 'open',
            'summary' => 'Suspicious link',
        ]);

        $this->signed('GET', '/api/hop/reports?status=open')
            ->assertOk()
            ->assertJsonPath('data.0.id', (string) $flag->id);

        $this->signed('POST', "/api/hop/reports/flag/{$flag->id}/actions", ['action' => 'restore', 'reason' => 'false positive'])
            ->assertOk()
            ->assertJsonPath('data.status', 'dismissed');
        $this->assertTrue((bool) $page->fresh()->is_published);

        $this->signed('GET', '/api/hop/settings')
            ->assertOk()
            ->assertJsonStructure(['ok', 'data']);

        $this->signed('GET', '/api/hop/search?q=siti')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'siti');

        $this->signed('GET', '/api/hop/audit')
            ->assertOk()
            ->assertJsonPath('data.0.action', 'restore');
    }

    public function test_account_deleted_event_and_internal_purge_clean_up_bio_uploads(): void
    {
        $this->withMiddleware(ValidateCsrfToken::class);
        Storage::fake('public');
        Storage::disk('public')->put('bio-images/avatar-del.png', 'png');
        Storage::disk('public')->put('bio-thumbnails/thumb-del.png', 'png');
        Storage::disk('public')->put('bio-icons/icon-hop1.png', 'png');

        $this->hubSigned('DELETE', '/internal/accounts/777777', [
            'bio_uploads' => ['bio-images/avatar-del.png'],
        ])->assertOk();
        Storage::disk('public')->assertMissing('bio-images/avatar-del.png');

        $this->hubSigned('POST', '/hop/events', [
            'id' => 'evt-bio-1',
            'type' => 'account.deleted',
            'data' => [
                'sub' => '777777',
                'bio_uploads' => ['bio-thumbnails/thumb-del.png'],
            ],
        ])->assertOk();
        Storage::disk('public')->assertMissing('bio-thumbnails/thumb-del.png');

        // Also test HOP-1 signed event from the hub
        HopApp::create([
            'slug' => 'helink',
            'name' => 'HOP ID',
            'url' => 'https://hub.hel.ink',
            'secret' => self::HUB_SECRET,
            'capabilities' => ['events'],
            'status' => HopApp::STATUS_ACTIVE,
        ]);
        $this->signed('POST', '/hop/events', [
            'id' => 'evt-bio-2',
            'type' => 'account.deleted',
            'data' => [
                'sub' => '777777',
                'bio_uploads' => ['bio-icons/icon-hop1.png'],
            ],
        ], app: 'helink', secret: self::HUB_SECRET)->assertOk();
        Storage::disk('public')->assertMissing('bio-icons/icon-hop1.png');

        // Sibling/admin apps in the registry with valid signatures must be rejected with 403
        Storage::disk('public')->put('bio-icons/must-keep.png', 'png');
        foreach (['pixelhop', 'notehop', 'imperium', 'citadel'] as $slug) {
            $appSecret = $slug.'-secret-that-is-at-least-32-bytes-long-123';
            HopApp::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucfirst($slug),
                    'url' => "https://{$slug}.hel.ink",
                    'secret' => $appSecret,
                    'capabilities' => ['events', 'admin_api', 'admin_client'],
                    'status' => HopApp::STATUS_ACTIVE,
                ]
            );
            $this->signed('POST', '/hop/events', [
                'id' => "evt-forbid-{$slug}",
                'type' => 'account.deleted',
                'data' => [
                    'sub' => '777777',
                    'bio_uploads' => ['bio-icons/must-keep.png'],
                ],
            ], app: $slug, secret: $appSecret)->assertStatus(403);
        }
        Storage::disk('public')->assertExists('bio-icons/must-keep.png');
    }

    public function test_csp_allows_cloudflare_turnstile_and_data_connect_src(): void
    {
        $csp = (string) $this->get('/login')->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('https://challenges.cloudflare.com', $csp);
        $this->assertMatchesRegularExpression('#connect-src [^;]*data:#', $csp);
    }
}
