<?php

namespace App\Domains\Clients\Jobs;

use App\Domains\Clients\Models\Client;
use App\Domains\Communications\Models\Communication;
use App\Domains\Dashboard\Support\DashboardCache;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class UpdateClientStatusesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $now = CarbonImmutable::now();

        Client::query()
            ->select(['id', 'last_communication_date', 'status', 'created_at'])
            ->chunkById(200, function (Collection $clients) use ($now): void {
                $clients->each(function (Client $client) use ($now): void {
                    $lastCommunication = null;

                    if ($client->last_communication_date) {
                        $lastCommunication = CarbonImmutable::parse($client->last_communication_date);
                    } elseif ($client->created_at) {
                        $lastCommunication = CarbonImmutable::parse($client->created_at);
                    }

                    if (! $lastCommunication) {
                        return;
                    }

                    $diffInDays = $lastCommunication->diffInDays($now);
                    
                    // Check communication count in last 7 days for "Hot" status
                    $recentCommsCount = Communication::query()
                        ->where('client_id', $client->id)
                        ->where('date', '>=', $now->subDays(7))
                        ->count();
                    
                    $newStatus = $this->determineStatus($diffInDays, $recentCommsCount);

                    if ($newStatus !== $client->status) {
                        $oldStatus = $client->status;
                        $client->forceFill(['status' => $newStatus])->save();
                        DashboardCache::flush();
                        Log::info('Client status auto-updated', [
                            'client_id' => $client->id,
                            'old_status' => $oldStatus,
                            'new_status' => $newStatus,
                            'days_since_last_comm' => $diffInDays,
                            'recent_comms_count' => $recentCommsCount,
                        ]);
                    }
                });
            });
    }

    /**
     * Determine client status based on communication patterns
     * 
     * Hot: 3+ communications in last 7 days OR last communication within 7 days
     * Warm: Last communication 8-30 days ago
     * Inactive: Last communication > 30 days ago
     */
    private function determineStatus(int $diffInDays, int $recentCommsCount): string
    {
        // Hot if 3+ communications in last week (as per requirements)
        if ($recentCommsCount >= 3) {
            return Client::STATUS_HOT;
        }
        
        // Also hot if very recent communication (within 7 days)
        if ($diffInDays <= 7) {
            return Client::STATUS_HOT;
        }
        
        // Warm if communication within last 30 days
        if ($diffInDays <= 30) {
            return Client::STATUS_WARM;
        }
        
        // Inactive if no communication for 30+ days
        return Client::STATUS_INACTIVE;
    }
}
