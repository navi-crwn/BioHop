<?php

namespace App\Console\Commands;

use App\Bio\FamilyUrls;
use Hop\Bio\Models\BioLink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Blocks that point at a short link (hel.ink/l/slug) whose target is another HOP site (note, pixel, link, bio,
 * hel.ink itself) get the target back. Those links were wrapped when the editor still shortened everything.
 * Shows what it would do; --apply writes it.
 */
class UnwrapFamilyLinks extends Command
{
    protected $signature = 'bio:unwrap-family-links {--apply : Write the changes (default: only list them)}';

    protected $description = 'Replace short links that lead to HOP sites with the real address in bio blocks';

    public function handle(): int
    {
        if (! Schema::hasTable('links')) {
            $this->warn('No links table here: nothing to unwrap.');

            return self::SUCCESS;
        }
        $changed = 0;
        BioLink::query()->where('url', 'like', '%/l/%')->orderBy('id')->chunkById(200, function ($blocks) use (&$changed) {
            foreach ($blocks as $block) {
                if (! preg_match('#^https?://(?:www\.)?(?:link\.)?hel\.ink/l/([A-Za-z0-9_-]+)/?$#i', (string) $block->url, $m)) {
                    continue;
                }
                $target = DB::table('links')->where('slug', $m[1])->value('target_url');
                if (! $target || ! FamilyUrls::isFamily($target)) {
                    continue;
                }
                $this->line("block #{$block->id}  {$block->url}  ->  {$target}");
                if ($this->option('apply')) {
                    $block->forceFill(['url' => $target])->save();
                }
                $changed++;
            }
        });
        $this->info(($this->option('apply') ? 'Changed' : 'Would change')." {$changed} block(s).");

        return self::SUCCESS;
    }
}
