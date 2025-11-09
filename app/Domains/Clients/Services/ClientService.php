<?php

namespace App\Domains\Clients\Services;

use App\Domains\Clients\Models\Client;
use App\Domains\Dashboard\Support\DashboardCache;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class ClientService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->baseQueryForUser($user, $filters)
            ->latest('clients.updated_at')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listForUser(User $user, array $filters = []): Collection
    {
        return $this->baseQueryForUser($user, $filters)->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, array $attributes): Client
    {
        Gate::authorize('create', Client::class);

        $attributes = $this->sanitizeAttributes($attributes);
        $attributes['status'] = $attributes['status'] ?? Client::STATUS_WARM;

        if ($user->hasRole('sales_rep') && ! Arr::has($attributes, 'assigned_to')) {
            $attributes['assigned_to'] = $user->id;
        }

        $client = Client::create($attributes);

        DashboardCache::flush();

        return $client->refresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, Client $client, array $attributes): Client
    {
        Gate::authorize('update', $client);

        $attributes = $this->sanitizeAttributes($attributes);

        if ($user->hasRole('sales_rep')) {
            $attributes['assigned_to'] = $client->assigned_to ?? $user->id;
        }

        $client->fill($attributes);

        $client->save();

        DashboardCache::flush();

        return $client->refresh();
    }

    public function delete(User $user, Client $client): void
    {
        Gate::authorize('delete', $client);

        $client->delete();

//        DashboardCache::flush();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function queryForUser(User $user, array $filters = []): Builder
    {
        return $this->baseQueryForUser($user, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function baseQueryForUser(User $user, array $filters = []): Builder
    {
        $query = Client::query()->with(['assignedRep']);

        if ($status = Arr::get($filters, 'status')) {
            $query->where('status', $status);
        }

        if ($user->hasRole('admin')) {
            return $query;
        }

        if ($user->hasRole('manager')) {
            $teamIds = $user->teamMembers()->pluck('id');

            return $query->where(function (Builder $builder) use ($user, $teamIds): void {
                $builder->whereNull('assigned_to')
                    ->orWhere('assigned_to', $user->id)
                    ->orWhereIn('assigned_to', $teamIds);
            });
        }

        return $query->where('assigned_to', $user->id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function sanitizeAttributes(array $attributes): array
    {
        return Arr::only($attributes, [
            'name',
            'email',
            'phone',
            'status',
            'assigned_to',
        ]);
    }

    /**
     * @param  string|array<string>  $abilities
     */
    protected function authorize(string|array $abilities, mixed $arguments = []): void
    {
        // Deprecated helper retained for backwards compatibility.
        foreach ((array) $abilities as $ability) {
            Gate::authorize($ability, $arguments);
        }
    }
}
