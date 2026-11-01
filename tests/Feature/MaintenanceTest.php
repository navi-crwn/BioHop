<?php

use App\Models\AbuseFlag;
use App\Models\User;
use Hop\Bio\Models\BioClick;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Models\BioWebhookDelivery;
use Illuminate\Support\Facades\DB;

test('privacy:prune deletes bio clicks older than 30 days only', function () {
    $page = BioPage::create(['user_id' => User::factory()->create()->id, 'slug' => 'prunepage', 'title' => 'P', 'theme' => 'default', 'is_published' => true]);
    $link = BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Go', 'url' => 'https://example.com', 'is_active' => true, 'order' => 0]);
    BioClick::create(['bio_link_id' => $link->id, 'clicked_at' => now()->subDays(31)]);
    BioClick::create(['bio_link_id' => $link->id, 'clicked_at' => now()->subDays(2)]);
    $this->artisan('privacy:prune')->expectsOutputToContain('bio clicks: 1')->assertSuccessful();
    expect(BioClick::count())->toBe(1);
});

test('privacy:prune deletes webhook deliveries after a week, since they hold the form entry', function () {
    $page = BioPage::create(['user_id' => User::factory()->create()->id, 'slug' => 'hookprune', 'title' => 'H', 'theme' => 'default', 'is_published' => true]);
    $make = fn (string $uuid, int $days) => tap(BioWebhookDelivery::create(['bio_page_id' => $page->id, 'uuid' => $uuid, 'event' => 'form.entry', 'payload' => '{"name":"A"}', 'status' => 'delivered']), fn ($d) => $d->forceFill(['created_at' => now()->subDays($days)])->save());
    $make('old-one', 8);
    $make('new-one', 2);
    $this->artisan('privacy:prune')->expectsOutputToContain('webhook deliveries: 1')->assertSuccessful();
    expect(BioWebhookDelivery::pluck('uuid')->all())->toBe(['new-one']);
});

test('abuse:scan unpublishes a spam bio page and records the finding', function () {
    $owner = User::factory()->create();
    $page = BioPage::create(['user_id' => $owner->id, 'slug' => 'gacorpage', 'title' => 'Slot gacor maxwin', 'bio' => 'judi online', 'theme' => 'default', 'is_published' => true]);
    $clean = BioPage::create(['user_id' => $owner->id, 'slug' => 'cleanpage', 'title' => 'My photos', 'theme' => 'default', 'is_published' => true]);
    $this->artisan('abuse:scan --full')->assertSuccessful();
    expect($page->fresh()->is_published)->toBeFalse()
        ->and($clean->fresh()->is_published)->toBeTrue()
        ->and(AbuseFlag::where('subject_type', 'bio_page')->where('subject_id', $page->id)->where('action', 'disabled')->exists())->toBeTrue();
    // The owner may not switch it back on while the takedown stands.
    $this->actingAs($owner)->putJson('/bio/'.$page->slug, ['slug' => 'gacorpage', 'is_published' => true])->assertStatus(422);
});

test('the sitemap lists indexable public bio pages on the public host', function () {
    $owner = User::factory()->create();
    BioPage::create(['user_id' => $owner->id, 'slug' => 'listed', 'title' => 'L', 'theme' => 'default', 'is_published' => true, 'allow_indexing' => true]);
    BioPage::create(['user_id' => $owner->id, 'slug' => 'hidden', 'title' => 'H', 'theme' => 'default', 'is_published' => true, 'allow_indexing' => false]);
    $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')
        ->assertSee('http://localhost/b/listed')->assertDontSee('/b/hidden');
});

test('public pages carry the CSP with the bio embed hosts and no framing', function () {
    $page = BioPage::create(['user_id' => User::factory()->create()->id, 'slug' => 'csppage', 'title' => 'C', 'theme' => 'default', 'is_published' => true]);
    $csp = $this->get('/b/csppage')->assertOk()->headers->get('Content-Security-Policy');
    expect($csp)->toContain('frame-src', 'youtube', "frame-ancestors 'none'", 'https://hub.test');
});

test('migrations are logged in migrations_bio and the baseline marks the old bio migrations done', function () {
    expect(config('database.migrations.table'))->toBe('migrations_bio');
    $logged = DB::table('migrations_bio')->pluck('migration');
    expect($logged)->toContain('2025_12_01_163337_create_bio_pages_table', '2026_10_28_000001_create_bio_linkhop_tokens_table');
    // Simulate production: tables exist, the log is empty. Running the baseline again pre-records the old ones.
    DB::table('migrations_bio')->where('migration', 'like', '20%')->delete();
    (require base_path('packages/bio/database/migrations/0000_00_00_000000_baseline_existing_bio_tables.php'))->up();
    $again = DB::table('migrations_bio')->pluck('migration');
    expect($again)->toContain('2025_12_01_163337_create_bio_pages_table', '2026_10_18_000001_default_bio_link_icon_invert_off')
        ->not->toContain('2026_10_28_000001_create_bio_linkhop_tokens_table');
    // And every bio migration can run again on an already-migrated schema without error.
    $this->artisan('migrate')->assertSuccessful();
});
