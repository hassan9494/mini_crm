<?php

namespace App\Domains\FollowUps\Jobs;

use App\Domains\FollowUps\Events\FollowUpDue;
use App\Domains\FollowUps\Models\FollowUp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

class CheckFollowUpsDueTodayJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = Carbon::today();

        FollowUp::query()
            ->whereDate('due_date', '<=', $today)
            ->where('status', FollowUp::STATUS_PENDING)
            ->chunkById(100, function ($followUps): void {
                foreach ($followUps as $followUp) {
                    Event::dispatch(new FollowUpDue($followUp));
                }
            });
    }
}
