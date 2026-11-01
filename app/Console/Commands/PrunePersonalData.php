<?php

namespace App\Console\Commands;

use Hop\Bio\Models\BioClick;
use Hop\Bio\Models\BioLead;
use Hop\Bio\Models\BioLink;
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
        // Form entries stay for the days the page owner chose on each form block (1–365, 90 by default).
        $leads = 0;
        BioLink::where('type', 'form')->whereHas('leads')->chunkById(200, function ($forms) use (&$leads) {
            foreach ($forms as $form) {
                $leads += BioLead::where('bio_link_id', $form->id)->where('created_at', '<', now()->subDays($form->leadRetentionDays()))->delete();
            }
        });
        $this->info("form entries: {$leads}");

        return self::SUCCESS;
    }
}
