<?php

namespace App\Domains\Clients\Policies;

use App\Domains\Clients\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        if ($user->hasRole('manager')) {
            return true;
        }

        if ($user->hasRole('sales_rep')) {
            return true;
        }

        return false;
    }

    public function view(User $user, Client $client): bool
    {
        if ($user->hasRole('manager')) {
            return $client->assigned_to === null || $client->assigned_to === $user->id || $user->teamMembers()->pluck('id')->contains($client->assigned_to);
        }

        if ($user->hasRole('sales_rep')) {
            return $client->assigned_to === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('clients.create');
    }

    public function update(User $user, Client $client): bool
    {
        if ($user->hasRole('manager')) {
            return $client->assigned_to === $user->id || $user->teamMembers()->pluck('id')->contains($client->assigned_to);
        }

        if ($user->hasRole('sales_rep')) {
            return $client->assigned_to === $user->id;
        }

        return false;
    }

    public function delete(User $user, Client $client): bool
    {
        if ($user->hasRole('manager')) {
            return $user->can('clients.delete') && ($client->assigned_to === $user->id || $user->teamMembers()->pluck('id')->contains($client->assigned_to));
        }

        if ($user->hasRole('sales_rep')) {
            return false;
        }

        return false;
    }
}
