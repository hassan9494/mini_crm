<?php

namespace App\Domains\Communications\Listeners;

use App\Domains\Communications\Events\CommunicationCreated;
use App\Domains\Dashboard\Support\DashboardCache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateClientLastContactListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CommunicationCreated $event): void
    {
        $communication = $event->communication;
        $communication->client->forceFill([
            'last_communication_date' => $communication->date,
        ])->save();

        DashboardCache::flush();
    }
}
