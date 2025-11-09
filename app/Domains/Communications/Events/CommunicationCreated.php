<?php

namespace App\Domains\Communications\Events;

use App\Domains\Communications\Models\Communication;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommunicationCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Communication $communication)
    {
    }
}
