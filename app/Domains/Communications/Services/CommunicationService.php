<?php

namespace App\Domains\Communications\Services;

use App\Domains\Clients\Models\Client;
use App\Domains\Communications\Events\CommunicationCreated;
use App\Domains\Communications\Models\Communication;
use App\Domains\Dashboard\Support\DashboardCache;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

class CommunicationService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForClient(User $user, Client $client, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        Gate::authorize('viewAny', [Communication::class, $client]);

        return $this->baseQuery($user, $client, $filters)
            ->latest('date')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, Client $client, array $attributes): Communication
    {
        Gate::authorize('create', [Communication::class, $client]);

        $payload = $this->sanitizeAttributes($attributes);
        $payload['client_id'] = $client->id;
        $payload['created_by'] = $user->id;

        $communication = Communication::create($payload);

        Event::dispatch(new CommunicationCreated($communication));

//        DashboardCache::flush();

        return $communication->refresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, Client $client, Communication $communication, array $attributes): Communication
    {
        Gate::authorize('update', [$communication, $client]);

        $communication->fill($this->sanitizeAttributes($attributes));
        $communication->save();

//        DashboardCache::flush();

        return $communication->refresh();
    }

    public function delete(User $user, Client $client, Communication $communication): void
    {
        Gate::authorize('delete', [$communication, $client]);

        $communication->delete();

//        DashboardCache::flush();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function baseQuery(User $user, Client $client, array $filters = []): Builder|\Illuminate\Database\Eloquent\Relations\HasMany
    {
        $query = $client->communications()->with('creator');

        if ($type = Arr::get($filters, 'type')) {
            $query->where('type', $type);
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
            'type',
            'date',
            'notes',
        ]);
    }
}
