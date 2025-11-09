<?php

namespace Tests\Unit\Listeners;

use App\Domains\FollowUps\Events\FollowUpDue;
use App\Domains\FollowUps\Listeners\SendFollowUpNotificationListener;
use App\Domains\FollowUps\Models\FollowUp;
use App\Domains\FollowUps\Notifications\FollowUpDueNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendFollowUpNotificationListenerTest extends TestCase
{
    public function test_sends_notification_to_owner(): void
    {
        Notification::fake();

        $followUp = FollowUp::factory()->create();

        $listener = new SendFollowUpNotificationListener();
        $listener->handle(new FollowUpDue($followUp));

        Notification::assertSentTo($followUp->owner, FollowUpDueNotification::class);
    }
}
