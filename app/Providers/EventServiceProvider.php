<?php

namespace App\Providers;

use App\Domains\Communications\Events\CommunicationCreated;
use App\Domains\Communications\Listeners\UpdateClientLastContactListener;
use App\Domains\FollowUps\Events\FollowUpDue;
use App\Domains\FollowUps\Listeners\SendFollowUpNotificationListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CommunicationCreated::class => [
            UpdateClientLastContactListener::class,
        ],
        FollowUpDue::class => [
            SendFollowUpNotificationListener::class,
        ],
    ];
}
