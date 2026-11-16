<?php

namespace Tests\Feature;

use App\Models\AbuseFlag;
use App\Models\AbuseReport;
use App\Models\HopApp;
use App\Models\HopAudit;
use App\Models\User;
use App\Support\HopSignature;
use Hop\Bio\Models\BioPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Reports that visitors send from bio.hel.ink/report-abuse reach BioHop's queue in Imperium (kind bio_report) and
 * can be acted on there, including hiding the reported page.
 */
class HopBioVisitorReportsTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'imperium-secret-that-is-long-enough-1234';

    protected function setUp(): void
    {
        parent::setUp();
        HopApp::factory()->imperium(self::SECRET)->create();
    }

    private function signed(string $method, string $path, array $body = [])
    {
        $json = $body ? json_encode($body) : '';
        $ts = (string) time();
        $nonce = HopSignature::b64(random_bytes(16));
        $sig = HopSignature::b64(hash_hmac('sha256', HopSignature::stringToSign('imperium', $ts, $nonce, $method, $path, $json), self::SECRET, true));

        return $this->call($method, $path, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_HOP_APP' => 'imperium',
            'HTTP_X_HOP_TIMESTAMP' => $ts,
            'HTTP_X_HOP_NONCE' => $nonce,
            'HTTP_X_HOP_ACTOR' => '7',
            'HTTP_X_HOP_SIGNATURE' => $sig,
        ], $json);
    }

    private function report(array $attributes = []): AbuseReport
    {
        return AbuseReport::create($attributes + [
            'slug' => 'siti', 'url' => 'https://hel.ink/b/siti', 'email' => 'reporter@example.com',
            'reason' => 'This page pretends to be my bank.', 'ip_address' => '203.0.113.9', 'status' => 'open', 'source' => 'biohop',
        ]);
    }

    private function page(string $slug = 'siti', ?User $user = null): BioPage
    {
        return BioPage::create(['user_id' => ($user ?? User::factory()->create(['username' => 'siti']))->id, 'slug' => $slug, 'title' => 'Siti', 'is_published' => true]);
    }

    private function dropSourceColumn(): void
    {
        Schema::table('abuse_reports', function ($table) {
            $table->dropIndex(['source']);
            $table->dropColumn('source');
        });
    }

    public function test_a_visitor_report_is_listed_with_its_page(): void
    {
        $page = $this->page();
        $report = $this->report();
        $this->report(['source' => 'linkhop', 'slug' => 'short', 'url' => 'https://link.hel.ink/short']);

        $row = $this->signed('GET', '/api/hop/reports?status=open')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->json('data.0');

        $this->assertSame('bio_report', $row['kind']);
        $this->assertSame('report-'.$report->id, $row['id']);
        $this->assertSame(['type' => 'bio_page', 'id' => (string) $page->id, 'slug' => 'siti', 'url' => public_url('b/siti')], $row['subject']);
        $this->assertSame('This page pretends to be my bank.', $row['reason']);
        $this->assertSame('reporter@example.com', $row['reporter']);
        $this->assertSame('open', $row['status']);
        $this->assertSame('siti', $row['owner']['username']);
        $this->assertNotNull($row['created_at']);
    }

    public function test_the_page_is_found_whatever_the_letter_case_of_the_reported_slug(): void
    {
        $page = $this->page('siti');
        $this->report(['slug' => 'SiTi']);

        $this->signed('GET', '/api/hop/reports')
            ->assertJsonPath('data.0.subject.id', (string) $page->id)
            ->assertJsonPath('data.0.subject.slug', 'siti');
    }

    public function test_the_slug_is_taken_from_the_pasted_address_when_the_form_sent_none(): void
    {
        $page = $this->page('siti');
        $this->report(['slug' => null, 'url' => 'https://hel.ink/b/Siti']);

        $this->signed('GET', '/api/hop/reports')->assertJsonPath('data.0.subject.id', (string) $page->id);
    }

    public function test_a_report_about_a_deleted_page_is_still_listed_without_a_page_id(): void
    {
        $this->report(['slug' => 'gone']);

        $this->signed('GET', '/api/hop/reports')
            ->assertOk()
            ->assertJsonPath('data.0.kind', 'bio_report')
            ->assertJsonPath('data.0.subject.id', null)
            ->assertJsonPath('data.0.subject.slug', 'gone')
            ->assertJsonPath('data.0.owner', null);
    }

    public function test_the_open_and_closed_filters(): void
    {
        $open = $this->report();
        $investigating = $this->report(['status' => 'investigating']);
        $closed = $this->report(['status' => 'closed']);

        $ids = fn (string $status) => collect($this->signed('GET', "/api/hop/reports?status={$status}")->assertOk()->json('data'))->pluck('id')->all();

        $this->assertEqualsCanonicalizing(['report-'.$open->id, 'report-'.$investigating->id], $ids('open'));
        $this->assertSame(['report-'.$closed->id], $ids('closed'));
        $this->assertCount(3, $ids('all'));
    }

    public function test_findings_and_visitor_reports_share_one_list_and_one_count(): void
    {
        $page = $this->page();
        AbuseFlag::create(['subject_type' => 'bio_page', 'subject_id' => $page->id, 'user_id' => $page->user_id, 'score' => 80, 'signals' => [], 'action' => 'disabled', 'status' => 'open', 'summary' => 'Suspicious link']);
        $this->report();
        $this->report(['status' => 'closed']);

        $kinds = collect($this->signed('GET', '/api/hop/reports?status=open')->json('data'))->pluck('kind')->sort()->values()->all();
        $this->assertSame(['bio_report', 'flag'], $kinds);
        $this->signed('GET', '/api/hop/stats')->assertJsonPath('data.totals.reports_open', 2);
        $this->signed('GET', '/api/hop/reports?status=closed')->assertJsonCount(1, 'data')->assertJsonPath('data.0.kind', 'bio_report');
    }

    public function test_investigate_close_and_reopen_only_change_the_report(): void
    {
        $page = $this->page();
        $report = $this->report();
        $path = '/api/hop/reports/report-'.$report->id.'/actions';

        $this->signed('POST', $path, ['action' => 'investigate'])->assertOk()->assertJsonPath('data.status', 'investigating');
        $this->assertSame('investigating', $report->fresh()->status);
        $this->signed('POST', $path, ['action' => 'close', 'reason' => 'checked, fine'])->assertOk()->assertJsonPath('data.status', 'closed');
        $this->signed('POST', $path, ['action' => 'reopen'])->assertOk()->assertJsonPath('data.status', 'open');

        $this->assertTrue((bool) $page->fresh()->is_published);
        $this->assertSame(0, AbuseFlag::count());
        $audit = HopAudit::where('target_type', 'bio_report')->orderBy('id')->get();
        $this->assertSame(['investigate', 'close', 'reopen'], $audit->pluck('action')->all());
        $this->assertSame('checked, fine', $audit[1]->reason);
    }

    public function test_the_route_with_an_explicit_kind_works_too(): void
    {
        $report = $this->report();

        $this->signed('POST', '/api/hop/reports/bio_report/'.$report->id.'/actions', ['action' => 'close'])
            ->assertOk()->assertJsonPath('data.status', 'closed');
    }

    public function test_unpublish_page_hides_the_page_flags_it_and_closes_the_report(): void
    {
        $page = $this->page();
        $report = $this->report();

        $this->signed('POST', '/api/hop/reports/report-'.$report->id.'/actions', ['action' => 'unpublish_page', 'reason' => 'phishing'])
            ->assertOk()
            ->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.subject.id', (string) $page->id);

        $this->assertFalse((bool) $page->fresh()->is_published);
        $flag = AbuseFlag::where('subject_type', 'bio_page')->where('subject_id', $page->id)->sole();
        $this->assertSame(AbuseFlag::STATUS_CONFIRMED, $flag->status);
        $this->assertSame('closed', $report->fresh()->status);
        $this->assertSame(['unpublish', 'unpublish_page'], HopAudit::orderBy('id')->pluck('action')->all());
        // An admin can undo it with the page action "publish".
        $this->signed('POST', "/api/hop/content/page/{$page->id}/actions", ['action' => 'publish'])->assertOk();
        $this->assertTrue((bool) $page->fresh()->is_published);
    }

    public function test_unpublish_page_is_refused_for_a_page_of_the_hop_owner(): void
    {
        $owner = User::factory()->create(['username' => 'boss', 'role' => 'superadmin']);
        DB::table('roles')->insert(['user_id' => $owner->id, 'role' => 'owner', 'created_at' => now(), 'updated_at' => now()]);
        $page = $this->page('boss', $owner);
        $report = $this->report(['slug' => 'boss']);

        $this->signed('POST', '/api/hop/reports/report-'.$report->id.'/actions', ['action' => 'unpublish_page'])
            ->assertStatus(403)->assertJsonPath('error', 'owner_protected');

        $this->assertTrue((bool) $page->fresh()->is_published);
        $this->assertSame('open', $report->fresh()->status);
        $this->assertSame(0, AbuseFlag::count());
    }

    public function test_unpublish_page_for_a_deleted_page_is_a_clear_404_and_the_report_stays_open(): void
    {
        $report = $this->report(['slug' => 'gone']);

        $this->signed('POST', '/api/hop/reports/report-'.$report->id.'/actions', ['action' => 'unpublish_page'])
            ->assertStatus(404)->assertJsonPath('error', 'page_not_found');
        $this->assertSame('open', $report->fresh()->status);

        $this->signed('POST', '/api/hop/reports/report-'.$report->id.'/actions', ['action' => 'close'])->assertOk();
    }

    public function test_unknown_action_unknown_report_and_reports_of_other_apps(): void
    {
        $report = $this->report();
        $other = $this->report(['source' => 'linkhop']);

        $this->signed('POST', '/api/hop/reports/report-'.$report->id.'/actions', ['action' => 'explode'])
            ->assertStatus(422)->assertJsonPath('error', 'unknown_action');
        $this->signed('POST', '/api/hop/reports/report-9999/actions', ['action' => 'close'])
            ->assertStatus(404);
        $this->signed('POST', '/api/hop/reports/report-'.$other->id.'/actions', ['action' => 'close'])
            ->assertStatus(404);
        $this->assertSame('open', $other->fresh()->status);
    }

    public function test_a_numeric_id_is_still_a_finding_and_never_a_visitor_report(): void
    {
        $page = $this->page();
        $report = $this->report();
        $flag = AbuseFlag::create(['subject_type' => 'bio_page', 'subject_id' => $page->id, 'user_id' => $page->user_id, 'score' => 80, 'signals' => [], 'action' => 'disabled', 'status' => 'open', 'summary' => 'x']);

        $this->signed('POST', '/api/hop/reports/'.$flag->id.'/actions', ['action' => 'dismiss'])
            ->assertOk()->assertJsonPath('data.kind', 'flag')->assertJsonPath('data.status', 'dismissed');
        $this->assertSame('open', $report->fresh()->status);
    }

    public function test_the_manifest_offers_the_buttons_of_the_report_kind(): void
    {
        $this->signed('GET', '/api/hop/manifest')
            ->assertOk()
            ->assertJsonPath('data.actions.report', ['investigate', 'close', 'unpublish_page']);
    }

    public function test_everything_keeps_working_while_the_source_column_does_not_exist_yet(): void
    {
        $page = $this->page();
        $flag = AbuseFlag::create(['subject_type' => 'bio_page', 'subject_id' => $page->id, 'user_id' => $page->user_id, 'score' => 80, 'signals' => [], 'action' => 'disabled', 'status' => 'open', 'summary' => 'x']);
        $this->dropSourceColumn();

        $this->signed('GET', '/api/hop/reports')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.kind', 'flag');
        $this->signed('GET', '/api/hop/stats')->assertOk()->assertJsonPath('data.totals.reports_open', 1);
        $this->signed('POST', '/api/hop/reports/report-1/actions', ['action' => 'close'])->assertStatus(404);
        $this->signed('POST', '/api/hop/reports/'.$flag->id.'/actions', ['action' => 'confirm'])->assertOk();
    }
}
