<?php

namespace App\Domains\FollowUps\Events;

use App\Domains\FollowUps\Models\FollowUp;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FollowUpDue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly FollowUp $followUp)
    {
    }
}
