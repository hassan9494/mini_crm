<?php

namespace App\Domains\Dashboard\Services;

use App\Domains\Clients\Models\Client;
use App\Domains\Clients\Services\ClientService;
use App\Domains\Dashboard\Support\DashboardCache;
use App\Domains\FollowUps\Models\FollowUp;
use App\Domains\Communications\Models\Communication;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(private readonly ClientService $clientService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetrics(User $user): array
    {
        return DashboardCache::remember($user, function () use ($user): array {
            $baseClientQuery = $this->clientService->queryForUser($user);
            $clientIdsQuery = (clone $baseClientQuery)->select('clients.id');

            $totalClients = (clone $baseClientQuery)->count();
            $statusBreakdown = $this->statusBreakdown($baseClientQuery);

            $now = CarbonImmutable::now();

            $pendingFollowUps = FollowUp::query()
                ->whereIn('client_id', $clientIdsQuery)
                ->where('status', FollowUp::STATUS_PENDING)
                ->count();

            $overdueFollowUps = FollowUp::query()
                ->whereIn('client_id', $clientIdsQuery)
                ->where('status', FollowUp::STATUS_PENDING)
                ->where('due_date', '<', $now)
                ->count();

            $dueSoonFollowUps = FollowUp::query()
                ->whereIn('client_id', $clientIdsQuery)
                ->where('status', FollowUp::STATUS_PENDING)
                ->whereBetween('due_date', [$now, $now->addDays(7)])
                ->count();

            $communicationsLastWeek = Communication::query()
                ->whereIn('client_id', $clientIdsQuery)
                ->where('date', '>=', $now->subDays(7))
                ->count();

            // Top 5 sales reps by active clients
            $topSalesReps = $this->getTopSalesReps($user);

            // Average communication frequency per client
            $avgCommunicationFrequency = $this->getAverageCommunicationFrequency($clientIdsQuery);

            return [
                'totals' => [
                    'clients' => $totalClients,
                    'follow_ups_pending' => $pendingFollowUps,
                    'follow_ups_overdue' => $overdueFollowUps,
                    'follow_ups_due_soon' => $dueSoonFollowUps,
                    'communications_last_7_days' => $communicationsLastWeek,
                ],
                'clients_by_status' => $statusBreakdown,
                'top_sales_reps' => $topSalesReps,
                'avg_communication_frequency_days' => $avgCommunicationFrequency,
                'generated_at' => $now->toISOString(),
            ];
        });
    }

    /**
     * @return array<string, int>
     */
    protected function statusBreakdown($baseClientQuery): array
    {
        $statuses = [
            Client::STATUS_HOT,
            Client::STATUS_WARM,
            Client::STATUS_INACTIVE,
        ];

        $breakdown = [];

        foreach ($statuses as $status) {
            $breakdown[$status] = (clone $baseClientQuery)
                ->where('status', $status)
                ->count();
        }

        return $breakdown;
    }

    /**
     * Get top 5 sales reps by active clients
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getTopSalesReps(User $user): array
    {
        // Only show for admins and managers
        if (!$user->hasRole('admin') && !$user->hasRole('manager')) {
            return [];
        }

        $query = User::query()
            ->whereHas('roles', fn($q) => $q->where('name', 'sales_rep'))
            ->withCount(['assignedClients' => function ($q) {
                $q->where('status', '!=', Client::STATUS_INACTIVE);
            }])
            ->orderByDesc('assigned_clients_count')
            ->limit(5)
            ->get();

        return $query->map(function (User $rep) {
            return [
                'id' => $rep->id,
                'name' => $rep->name,
                'email' => $rep->email,
                'active_clients_count' => $rep->assigned_clients_count,
            ];
        })->toArray();
    }

    /**
     * Calculate average days between communications per client
     *
     * @return float|null
     */
    protected function getAverageCommunicationFrequency($clientIdsQuery): ?float
    {
        $clients = Client::query()
            ->whereIn('id', $clientIdsQuery)
            ->whereNotNull('last_communication_date')
            ->where('last_communication_date', '>', CarbonImmutable::now()->subDays(90))
            ->get(['id', 'created_at', 'last_communication_date']);

        if ($clients->isEmpty()) {
            return null;
        }

        $totalDays = 0;
        $totalCommunications = 0;

        foreach ($clients as $client) {
            $communications = Communication::query()
                ->where('client_id', $client->id)
                ->orderBy('date')
                ->pluck('date')
                ->toArray();

            if (count($communications) < 2) {
                continue;
            }

            // Calculate days between consecutive communications
            for ($i = 1; $i < count($communications); $i++) {
                $prev = CarbonImmutable::parse($communications[$i - 1]);
                $current = CarbonImmutable::parse($communications[$i]);
                $totalDays += $prev->diffInDays($current);
                $totalCommunications++;
            }
        }

        return $totalCommunications > 0 ? round($totalDays / $totalCommunications, 1) : null;
    }
}
