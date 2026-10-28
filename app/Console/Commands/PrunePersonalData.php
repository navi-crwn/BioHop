<?php

namespace App\Console\Commands;

use Hop\Bio\Models\BioClick;
use Illuminate\Console\Command;

/**
 * Retention promised in the privacy policy: bio click logs (hashed visitor,
 * country, device) are kept 30 days. Account logs are pruned by the hub.
 */
class PrunePersonalData extends Command
{
    public const BIO_CLICK_DAYS = 30;

    protected $signature = 'privacy:prune';

    protected $description = 'Delete bio click logs older than the privacy policy allows (30 days)';

    public function handle(): int
    {
        $n = BioClick::where('clicked_at', '<', now()->subDays(self::BIO_CLICK_DAYS))->delete();
        $this->info("bio clicks: {$n}");

        return self::SUCCESS;
    }
}
