<?php

namespace App\Console;

use App\Domains\Clients\Jobs\UpdateClientStatusesJob;
use App\Domains\FollowUps\Jobs\CheckFollowUpsDueTodayJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(UpdateClientStatusesJob::class)->dailyAt('01:00');
        $schedule->job(CheckFollowUpsDueTodayJob::class)->dailyAt('08:00');
    }
}
