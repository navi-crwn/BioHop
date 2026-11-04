<?php

namespace App\Console\Commands;

use Hop\Safety\EncryptedNoteLink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the #k= / #a= part of encrypted NoteHop addresses that were saved before BioHop refused them.
 * Dry run unless --apply. Prints counts and row ids only, never the addresses. Only addresses on a NoteHop host
 * are touched. Sessions (old form input) and queue tables are only counted, not edited.
 */
class ScrubNoteKeys extends Command
{
    protected $signature = 'bio:scrub-note-keys {--apply : write the changes (default is a dry run)}';

    protected $description = 'Find (and with --apply remove) encrypted NoteHop keys stored in bio blocks, pages and abuse reports';

    /** table => string or JSON columns that can hold an address or free text. */
    private const COLUMNS = [
        'bio_links' => ['url', 'embed_url', 'thumbnail_url', 'title', 'description', 'content', 'settings'],
        'bio_pages' => ['bio', 'title', 'social_links', 'fallback_url', 'footer_url', 'webhook_url', 'seo_title', 'seo_description', 'custom_css'],
        'bio_revisions' => ['snapshot'],
        'abuse_reports' => ['url', 'reason'],
        'bio_webhook_deliveries' => ['payload'],
    ];

    public function handle(): int
    {
        $notes = new EncryptedNoteLink;
        $apply = (bool) $this->option('apply');
        $total = 0;
        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $columns = array_values(array_filter($columns, fn ($c) => Schema::hasColumn($table, $c)));
            if ($columns === []) {
                continue;
            }
            $hits = 0;
            $ids = [];
            DB::table($table)->select(['id', ...$columns])->orderBy('id')->chunkById(200, function ($rows) use ($notes, $table, $columns, $apply, &$hits, &$ids) {
                foreach ($rows as $row) {
                    $changes = [];
                    foreach ($columns as $c) {
                        $v = $row->{$c};
                        if (is_string($v) && $v !== '' && ($clean = $notes->scrubJson($v)) !== $v) {
                            $changes[$c] = $clean;
                        }
                    }
                    if ($changes !== []) {
                        $hits++;
                        $ids[] = $row->id;
                        if ($apply) {
                            DB::table($table)->where('id', $row->id)->update($changes);
                        }
                    }
                }
            });
            $total += $hits;
            $this->line(sprintf('%-24s rows with a key: %d%s', $table, $hits, $ids ? '  ids: '.implode(',', array_slice($ids, 0, 50)).(count($ids) > 50 ? ',…' : '') : ''));
        }
        foreach (['sessions' => 'payload', 'jobs' => 'payload', 'failed_jobs' => 'payload'] as $table => $column) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                $n = 0;
                DB::table($table)->select([$column])->cursor()->each(function ($row) use ($notes, $table, $column, &$n) {
                    $v = (string) $row->{$column};
                    if ($table === 'sessions') {
                        $v = (string) base64_decode($v, true); // the database session driver stores base64 of the serialized data
                    }
                    $v = str_replace('\\/', '/', $v);
                    if (stripos($v, 'k=') !== false && $notes->scrub($v) !== $v) {
                        $n++;
                    }
                });
                $this->line(sprintf('%-24s counted only (not edited): %d', $table, $n));
            }
        }
        $this->info($apply ? "scrubbed rows: {$total}" : "dry run, rows with a key: {$total} (use --apply to remove)");

        return self::SUCCESS;
    }
}
