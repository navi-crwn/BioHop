<?php

use App\Models\AbuseFlag;
use App\Models\HopApp;
use App\Models\User;
use App\Support\HopSignature;
use Hop\Bio\Models\BioPage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every column the admin content API (HopBioController, read by Imperium) names in a query must exist in the table
 * it is asked of. The tests run on SQLite, which reads an unknown double-quoted identifier as a string instead of
 * failing; MySQL in production answers "Unknown column" and the endpoint is a 500. That is how a search on
 * bio_pages.description (a column only bio_links has) passed every test. This reads the SQL the endpoints really
 * send and checks each identifier against the schema, so a wrong column fails here whatever the driver.
 */
const QUERY_COLUMNS_SECRET = 'imperium-secret-that-is-long-enough-1234';

function signedHopGet(string $path)
{
    $ts = (string) time();
    $nonce = HopSignature::b64(random_bytes(16));
    $sig = HopSignature::b64(hash_hmac('sha256', HopSignature::stringToSign('imperium', $ts, $nonce, 'GET', $path, ''), QUERY_COLUMNS_SECRET, true));

    return test()->call('GET', $path, [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_HOP_APP' => 'imperium',
        'HTTP_X_HOP_TIMESTAMP' => $ts, 'HTTP_X_HOP_NONCE' => $nonce, 'HTTP_X_HOP_ACTOR' => '7', 'HTTP_X_HOP_SIGNATURE' => $sig,
    ], '');
}

/**
 * The identifiers of one statement that are not in the schema, as "table.column" strings.
 * Qualified names ("t"."c") are checked against t. A bare name belongs to the table of the nearest enclosing
 * FROM / INTO / UPDATE; names after AS are aliases and are remembered, not checked.
 *
 * @param  array<string, list<string>>  $schema  table => columns
 * @return list<string>
 */
function unknownColumns(string $sql, array $schema): array
{
    preg_match_all('~\bas\s+"?(\w+)"?~i', $sql, $m);
    $aliases = array_flip($m[1]);
    // Tokens, in this order: a subquery opening, a plain parenthesis, a table after FROM/INTO/UPDATE/JOIN, an alias,
    // "table".*, "table"."column", and a bare "column".
    preg_match_all('~(\(\s*select\b)|\(|\)|\b(?:from|into|update|join)\s+"([^"]+)"|\bas\s+"[^"]+"|"[^"]+"\.\*|"([^"]+)"\."([^"]+)"|"([^"]+)"~i', $sql, $tokens, PREG_SET_ORDER);
    // One entry per open parenthesis. A query level (the statement itself or a subquery) has its own table and keeps
    // the bare names of its select list until its FROM is read; a plain group belongs to the query around it.
    $stack = [['query' => true, 'table' => null, 'pending' => []]];
    $unknown = [];
    $check = function (string $table, string $column) use ($schema, $aliases, &$unknown) {
        if (! isset($aliases[$column]) && isset($schema[$table]) && ! in_array($column, $schema[$table], true)) {
            $unknown[] = $table.'.'.$column;
        }
    };
    $queryLevel = function () use (&$stack): int {
        for ($i = count($stack) - 1; $i > 0; $i--) {
            if ($stack[$i]['query']) {
                return $i;
            }
        }

        return 0;
    };
    foreach ($tokens as $t) {
        if (($t[1] ?? '') !== '') {
            $stack[] = ['query' => true, 'table' => null, 'pending' => []];
        } elseif ($t[0] === '(') {
            $stack[] = ['query' => false, 'table' => null, 'pending' => []];
        } elseif ($t[0] === ')') {
            if (count($stack) > 1) {
                array_pop($stack);
            }
        } elseif (($t[2] ?? '') !== '') {
            $level = $queryLevel();
            if ($stack[$level]['table'] === null) {
                $stack[$level]['table'] = $t[2];
                foreach ($stack[$level]['pending'] as $column) {
                    $check($t[2], $column);
                }
                $stack[$level]['pending'] = [];
            }
        } elseif (($t[3] ?? '') !== '') {
            $check($t[3], $t[4]);
        } elseif (($t[5] ?? '') !== '') {
            $level = $queryLevel();
            if ($stack[$level]['table'] === null) {
                $stack[$level]['pending'][] = $t[5];
            } else {
                $check($stack[$level]['table'], $t[5]);
            }
        }
    }

    return array_values(array_unique($unknown));
}

function schemaColumns(): array
{
    $schema = [];
    foreach (Schema::getTableListing() as $table) {
        $name = str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;
        $schema[$name] = Schema::getColumnListing($name);
    }

    return $schema;
}

test('the checker itself tells a real column from one that only another table has', function () {
    $schema = schemaColumns();
    expect($schema)->toHaveKeys(['bio_pages', 'bio_links', 'users'])
        ->and(unknownColumns('select * from "bio_pages" where ("slug" like ? or "bio" like ? or "seo_description" like ?) order by "id" desc', $schema))->toBe([])
        ->and(unknownColumns('select * from "bio_pages" where ("slug" like ? or "description" like ?)', $schema))->toBe(['bio_pages.description'])
        ->and(unknownColumns('select * from "bio_links" where "description" like ?', $schema))->toBe([])
        ->and(unknownColumns('select "bio_pages".*, (select count(*) from "bio_links" where "bio_pages"."id" = "bio_links"."bio_page_id") as "links_count" from "bio_pages" where exists (select * from "users" where "bio_pages"."user_id" = "users"."id" and "nickname" = ?)', $schema))->toBe(['users.nickname'])
        ->and(unknownColumns('select "id", "nope" from "users" where "users"."id" in (1)', $schema))->toBe(['users.nope']);
});

test('every query of the admin content API names only columns its table has', function () {
    HopApp::factory()->imperium(QUERY_COLUMNS_SECRET)->create();
    $user = User::factory()->create(['username' => 'columnowner']);
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'columns', 'title' => 'Columns', 'bio' => 'needle bio', 'seo_description' => 'needle seo', 'is_published' => true]);
    $block = $page->links()->create(['type' => 'link', 'title' => 'needle link', 'url' => 'https://example.com/needle', 'order' => 0, 'is_active' => true]);
    AbuseFlag::create(['subject_type' => 'bio_page', 'subject_id' => $page->id, 'reason' => 'test', 'status' => AbuseFlag::STATUS_OPEN]);

    $schema = schemaColumns();
    $bad = [];
    DB::listen(function ($query) use ($schema, &$bad) {
        foreach (unknownColumns($query->sql, $schema) as $column) {
            $bad[$column][] = $query->sql;
        }
    });

    $paths = ['/api/hop/stats', '/api/hop/reports', '/api/hop/reports?status=open', '/api/hop/reports?status=closed', '/api/hop/audit',
        '/api/hop/search?q=needle', "/api/hop/content/page/{$page->id}", "/api/hop/content/bio/{$page->id}", "/api/hop/content/block/{$block->id}"];
    foreach (['page', 'bio'] as $type) {
        foreach (['', 'published', 'draft', 'adult', 'flagged'] as $status) {
            $paths[] = "/api/hop/content?type={$type}&q=needle&status={$status}";
        }
        $paths[] = "/api/hop/content?type={$type}&q=needle&owner=columnowner&cursor=999999";
        $paths[] = "/api/hop/content?type={$type}&owner={$user->id}";
    }
    foreach (['', 'active', 'disabled', 'flagged'] as $status) {
        $paths[] = "/api/hop/content?type=block&q=needle&status={$status}";
    }
    $paths[] = '/api/hop/content?type=block&q=needle&owner=columnowner&cursor=999999';
    $paths[] = "/api/hop/content?type=block&owner={$user->id}";

    foreach ($paths as $path) {
        $status = signedHopGet($path)->getStatusCode();
        expect($status)->toBeLessThan(500, $path.' answered '.$status);
    }
    expect($bad)->toBe([], 'columns that are not in their table: '.implode(', ', array_keys($bad)));

    // The search really looks in the two text columns bio_pages has.
    expect(signedHopGet('/api/hop/content?type=page&q=needle+bio')->json('page.total'))->toBe(1)
        ->and(signedHopGet('/api/hop/content?type=page&q=needle+seo')->json('page.total'))->toBe(1);
});
