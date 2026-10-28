<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BioHop took the bio tables over from LinkHop, where every migration in this
 * folder already ran (logged in LinkHop's `migrations` table). BioHop logs its
 * own migrations in `migrations_bio`, which starts empty. This baseline runs
 * first (its name sorts before every other): when bio_pages already exists it
 * marks all the older bio migrations as done in migrations_bio, so `migrate`
 * on the production database adds nothing and rolls nothing back. On a fresh
 * database (tests, a new install) it does nothing and the migrations run.
 *
 * The migrations themselves are idempotent too (hasTable/hasColumn guards),
 * so a half-migrated database is also safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bio_pages')) {
            return;
        }
        $table = config('database.migrations.table', 'migrations_bio');
        $done = DB::table($table)->pluck('migration')->all();
        $batch = (int) DB::table($table)->max('batch') + 1;
        $rows = [];
        foreach (glob(__DIR__.'/*.php') ?: [] as $file) {
            $name = basename($file, '.php');
            if ($name === basename(__FILE__, '.php') || in_array($name, $done, true) || str_starts_with($name, '0000_')) {
                continue;
            }
            // Only migrations dated before the hand-over: anything newer is BioHop's own and must run.
            if (strcmp($name, self::HANDOVER) > 0) {
                continue;
            }
            $rows[] = ['migration' => $name, 'batch' => $batch];
        }
        if ($rows !== []) {
            DB::table($table)->insert($rows);
        }
    }

    /** The last migration LinkHop ran before BioHop became the owner (names up to this one are pre-recorded). */
    public const HANDOVER = '2026_10_18_000001_default_bio_link_icon_invert_off';

    public function down(): void {}
};
