<?php

namespace Tests\Feature;

use App\Models\HopApp;
use App\Models\User;
use App\Support\HopSignature;
use Hop\Bio\Models\BioPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regression for the content API's page search and detail: bio_pages has no
 * `description` column (that one lives on bio_links). The text columns are
 * `bio` and `seo_description`. A predicate naming the missing column passes
 * silently on the SQLite test driver — a double-quoted identifier SQLite does
 * not know degrades to a string literal — while production MySQL rejects the
 * statement with "Unknown column" and answers 500. These tests pin the schema
 * contract and check the endpoints against the columns that really exist, so
 * SQLite can no longer hide a reference to `bio_pages.description`.
 */
class HopBioContentDescriptionRegressionTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'imperium-secret-that-is-long-enough-1234';

    protected function setUp(): void
    {
        parent::setUp();
        HopApp::factory()->imperium(self::SECRET)->create();
    }

    private function signed(string $method, string $path, string $actor = '7', string $app = 'imperium')
    {
        $ts = (string) time();
        $nonce = HopSignature::b64(random_bytes(16));
        $sig = HopSignature::b64(hash_hmac('sha256', HopSignature::stringToSign($app, $ts, $nonce, $method, $path, ''), self::SECRET, true));

        return $this->call($method, $path, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_HOP_APP' => $app,
            'HTTP_X_HOP_TIMESTAMP' => $ts,
            'HTTP_X_HOP_NONCE' => $nonce,
            'HTTP_X_HOP_ACTOR' => $actor,
            'HTTP_X_HOP_SIGNATURE' => $sig,
        ], '');
    }

    public function test_bio_pages_has_bio_and_seo_description_but_never_a_description_column(): void
    {
        $pageCols = Schema::getColumnListing('bio_pages');
        $linkCols = Schema::getColumnListing('bio_links');

        $this->assertContains('bio', $pageCols);
        $this->assertContains('seo_description', $pageCols);
        $this->assertNotContains('description', $pageCols);

        // The description column exists, but only on bio_links.
        $this->assertContains('description', $linkCols);
        $this->assertContains('content', $linkCols);
    }

    public function test_content_listing_search_finds_a_page_by_its_bio_text(): void
    {
        $user = User::factory()->create(['username' => 'biosearcher']);
        $page = BioPage::create([
            'user_id' => $user->id,
            'slug' => 'quiet-slug',
            'title' => 'Quiet Title',
            'bio' => 'I ship kiln9241 brick ovens from my garage.',
            'is_published' => true,
        ]);

        $res = $this->signed('GET', '/api/hop/content?type=page&q=kiln9241');
        $res->assertOk();
        $this->assertSame(1, $res->json('page.total'));
        $this->assertSame($page->id, (int) $res->json('data.0.id'));
        $this->assertSame('quiet-slug', $res->json('data.0.slug'));
    }

    public function test_content_listing_search_finds_a_page_by_its_seo_description(): void
    {
        $user = User::factory()->create(['username' => 'seosearcher']);
        $page = BioPage::create([
            'user_id' => $user->id,
            'slug' => 'plain-slug',
            'title' => 'Plain Title',
            'bio' => null,
            'seo_description' => 'Handmade glaze4407 pottery, fired twice.',
            'is_published' => true,
        ]);

        $res = $this->signed('GET', '/api/hop/content?type=bio&q=glaze4407');
        $res->assertOk();
        $this->assertSame(1, $res->json('page.total'));
        $this->assertSame($page->id, (int) $res->json('data.0.id'));
        $this->assertSame('bio', $res->json('data.0.type'));
    }

    public function test_content_detail_reports_the_stored_bio_text_as_description(): void
    {
        $user = User::factory()->create(['username' => 'detailowner']);
        $page = BioPage::create([
            'user_id' => $user->id,
            'slug' => 'detail-slug',
            'title' => 'Detail Title',
            'bio' => 'A short line about pottery6823.',
            'is_published' => true,
        ]);

        $this->signed('GET', "/api/hop/content/page/{$page->id}")
            ->assertOk()
            ->assertJsonPath('data.description', 'A short line about pottery6823.')
            ->assertJsonPath('data.slug', 'detail-slug');
    }

    public function test_content_queries_never_name_a_description_column_on_bio_pages(): void
    {
        $user = User::factory()->create(['username' => 'logowner']);
        BioPage::create([
            'user_id' => $user->id,
            'slug' => 'logged-slug',
            'title' => 'Logged Title',
            'bio' => 'Something searchable8855 here.',
            'is_published' => true,
        ]);

        DB::enableQueryLog();
        $this->signed('GET', '/api/hop/content?type=page&q=searchable8855')->assertOk();
        $this->signed('GET', '/api/hop/content?type=bio&q=searchable8855')->assertOk();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $pageQueries = array_values(array_filter($queries, fn ($q) => str_contains($q['query'], 'bio_pages')));
        $this->assertNotEmpty($pageQueries);
        foreach ($pageQueries as $q) {
            // `seo_description` must stay untouched: the word boundary only fires on a bare `description`.
            $this->assertSame(0, preg_match('/\bdescription\b/', $q['query']), $q['query']);
        }
    }

    public function test_content_listing_combines_query_owner_status_and_pagination(): void
    {
        $owner = User::factory()->create(['username' => 'comboowner']);
        $other = User::factory()->create(['username' => 'comboother']);
        $token = 'sevensix5421';

        $first = BioPage::create(['user_id' => $owner->id, 'slug' => 'combo-a1', 'title' => 'A one', 'bio' => "{$token} here", 'is_published' => true]);
        $second = BioPage::create(['user_id' => $owner->id, 'slug' => 'combo-a2', 'title' => 'A two', 'bio' => "{$token} again", 'is_published' => true]);
        BioPage::create(['user_id' => $owner->id, 'slug' => 'combo-a3', 'title' => 'A three', 'bio' => "{$token} draft", 'is_published' => false]);
        BioPage::create(['user_id' => $other->id, 'slug' => 'combo-b1', 'title' => 'B one', 'bio' => "{$token} other owner", 'is_published' => true]);

        $pageOne = $this->signed('GET', "/api/hop/content?type=page&q={$token}&owner=comboowner&status=published&limit=1");
        $pageOne->assertOk();
        $this->assertSame(2, $pageOne->json('page.total'));
        $this->assertCount(1, $pageOne->json('data'));
        $this->assertNotNull($pageOne->json('page.next'));
        $firstRow = $pageOne->json('data.0');

        // Observed contract: page.total counts the rows left after the cursor filter, not the whole filtered set
        // (index() counts the same query that carries the cursor). Pinned as-is; changing it is out of P3b scope.
        $pageTwo = $this->signed('GET', "/api/hop/content?type=page&q={$token}&owner=comboowner&status=published&limit=1&cursor={$firstRow['id']}");
        $pageTwo->assertOk();
        $this->assertSame(1, $pageTwo->json('page.total'));
        $this->assertCount(1, $pageTwo->json('data'));
        $secondRow = $pageTwo->json('data.0');

        $pageThree = $this->signed('GET', "/api/hop/content?type=page&q={$token}&owner=comboowner&status=published&limit=1&cursor={$secondRow['id']}");
        $pageThree->assertOk();
        $this->assertSame(0, $pageThree->json('page.total'));
        $this->assertCount(0, $pageThree->json('data'));
        $this->assertNull($pageThree->json('page.next'));

        // The two pages handed out across the two requests are exactly the published matches of that owner;
        // the draft and the other owner's page never appear.
        $returned = [$firstRow['id'], $secondRow['id']];
        sort($returned);
        $expected = [(string) $first->id, (string) $second->id];
        sort($expected);
        $this->assertSame($expected, $returned);
    }

    public function test_content_detail_accepts_type_bio_and_echoes_the_requested_type(): void
    {
        $user = User::factory()->create(['username' => 'typoechoer']);
        $page = BioPage::create([
            'user_id' => $user->id,
            'slug' => 'echo-slug',
            'title' => 'Echo Title',
            'bio' => 'One line of bio text5533.',
            'is_published' => true,
        ]);

        $this->signed('GET', "/api/hop/content/bio/{$page->id}")
            ->assertOk()
            ->assertJsonPath('data.type', 'bio')
            ->assertJsonPath('data.id', (string) $page->id)
            ->assertJsonPath('data.description', 'One line of bio text5533.');

        $this->signed('GET', "/api/hop/content/page/{$page->id}")
            ->assertOk()
            ->assertJsonPath('data.type', 'page');
    }

    public function test_content_detail_description_is_null_when_the_page_has_no_bio_text(): void
    {
        $user = User::factory()->create(['username' => 'nullbioowner']);
        $page = BioPage::create([
            'user_id' => $user->id,
            'slug' => 'nullbio-slug',
            'title' => 'Null Bio Title',
            'bio' => null,
            'seo_description' => 'Meta text only8814.',
            'is_published' => true,
        ]);

        $res = $this->signed('GET', "/api/hop/content/page/{$page->id}");
        $res->assertOk();
        $data = $res->json('data');
        $this->assertArrayHasKey('description', $data);
        $this->assertNull($data['description']);
    }
}
