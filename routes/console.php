<?php

use App\Jobs\QueueHeartbeat;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new QueueHeartbeat)->everyTwoMinutes();
Schedule::command('abuse:scan --since=2h')->hourly()->withoutOverlapping();
Schedule::command('abuse:scan --full --purge')->dailyAt('04:10')->withoutOverlapping();
Schedule::command('privacy:prune')->dailyAt('03:30');
Schedule::command('abuse:feeds')->dailyAt('02:15'); // hop/safety threat feeds
