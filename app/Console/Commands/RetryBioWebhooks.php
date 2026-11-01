<?php

namespace App\Console\Commands;

use Hop\Bio\Support\Webhooks;
use Illuminate\Console\Command;

class RetryBioWebhooks extends Command
{
    protected $signature = 'bio:webhooks:retry';

    protected $description = 'Send the page webhooks whose next try is due';

    public function handle(): int
    {
        $n = Webhooks::retryDue();
        if ($n) {
            $this->info("webhook tries: {$n}");
        }

        return self::SUCCESS;
    }
}
