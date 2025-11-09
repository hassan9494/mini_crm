<?php

namespace Tests\Unit\Jobs;

use App\Domains\FollowUps\Events\FollowUpDue;
use App\Domains\FollowUps\Jobs\CheckFollowUpsDueTodayJob;
use App\Domains\FollowUps\Models\FollowUp;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CheckFollowUpsDueTodayJobTest extends TestCase
{
    public function test_dispatches_events_for_pending_follow_ups_due_today(): void
    {
        $today = Carbon::parse('2025-03-01 00:00:00');
        Carbon::setTestNow($today);

        Event::fake([FollowUpDue::class]);

        FollowUp::factory()->count(2)->create([
            'due_date' => $today,
            'status' => FollowUp::STATUS_PENDING,
        ]);

        FollowUp::factory()->create([
            'due_date' => $today->copy()->subDay(),
            'status' => FollowUp::STATUS_PENDING,
        ]);

        FollowUp::factory()->create([
            'due_date' => $today->copy()->addDay(),
            'status' => FollowUp::STATUS_PENDING,
        ]);

        FollowUp::factory()->create([
            'due_date' => $today,
            'status' => FollowUp::STATUS_COMPLETED,
        ]);

        (new CheckFollowUpsDueTodayJob())->handle();

        Event::assertDispatchedTimes(FollowUpDue::class, 3);
        Carbon::setTestNow();
    }
}
