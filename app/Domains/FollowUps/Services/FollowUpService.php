<?php

namespace App\Domains\FollowUps\Services;

use App\Domains\Clients\Models\Client;
use App\Domains\Dashboard\Support\DashboardCache;
use App\Domains\FollowUps\Models\FollowUp;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class FollowUpService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForClient(User $user, Client $client, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        Gate::authorize('viewAny', [FollowUp::class, $client]);

        return $this->baseQuery($client, $filters)
            ->orderBy('due_date')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, Client $client, array $attributes): FollowUp
    {
        Gate::authorize('create', [FollowUp::class, $client]);

        $payload = $this->sanitizeAttributes($attributes);
        $payload['client_id'] = $client->id;
        $payload['user_id'] = $payload['user_id'] ?? $user->id;
        $payload['status'] = $payload['status'] ?? FollowUp::STATUS_PENDING;

        $followUp = FollowUp::create($payload);

        DashboardCache::flush();

        return $followUp->load('owner');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, Client $client, FollowUp $followUp, array $attributes): FollowUp
    {
        Gate::authorize('update', [$followUp, $client]);

        $followUp->fill($this->sanitizeAttributes($attributes));
        $followUp->save();

        DashboardCache::flush();

        return $followUp->refresh()->load('owner');
    }

    public function delete(User $user, Client $client, FollowUp $followUp): void
    {
        Gate::authorize('delete', [$followUp, $client]);

        $followUp->delete();

        DashboardCache::flush();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function baseQuery(Client $client, array $filters = []): Builder|HasMany
    {
        $query = $client->followUps()->with('owner');

        if ($status = Arr::get($filters, 'status')) {
            $query->where('status', $status);
        }

        if ($from = Arr::get($filters, 'due_from')) {
            $query->whereDate('due_date', '>=', $from);
        }

        if ($to = Arr::get($filters, 'due_to')) {
            $query->whereDate('due_date', '<=', $to);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function sanitizeAttributes(array $attributes): array
    {
        return Arr::only($attributes, [
            'due_date',
            'notes',
            'status',
            'user_id',
        ]);
    }
}
