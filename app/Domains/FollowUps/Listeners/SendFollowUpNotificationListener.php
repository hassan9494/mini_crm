<?php

namespace App\Domains\FollowUps\Listeners;

use App\Domains\FollowUps\Events\FollowUpDue;
use App\Domains\FollowUps\Notifications\FollowUpDueNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendFollowUpNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(FollowUpDue $event): void
    {
        $followUp = $event->followUp->loadMissing(['owner', 'client']);

        if ($followUp->owner) {
            $followUp->owner->notify(new FollowUpDueNotification($followUp));
        }
    }
}
