<?php

namespace App\Domains\FollowUps\Policies;

use App\Domains\Clients\Models\Client;
use App\Domains\FollowUps\Models\FollowUp;
use App\Models\User;

class FollowUpPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user, Client $client): bool
    {
        return $this->canAccessClient($user, $client);
    }

    public function view(User $user, FollowUp $followUp, Client $client): bool
    {
        return $this->canAccessClient($user, $client);
    }

    public function create(User $user, Client $client): bool
    {
        return $user->can('followups.manage') && $this->canAccessClient($user, $client, true);
    }

    public function update(User $user, FollowUp $followUp, Client $client): bool
    {
        return $user->can('followups.manage') && $this->canAccessClient($user, $client);
    }

    public function delete(User $user, FollowUp $followUp, Client $client): bool
    {
        return $user->can('followups.manage') && $this->canAccessClient($user, $client);
    }

    private function canAccessClient(User $user, Client $client, bool $allowUnassigned = false): bool
    {
        if ($user->hasRole('manager')) {
            $teamMemberIds = $user->teamMembers()->pluck('id');

            return ($allowUnassigned && is_null($client->assigned_to))
                || $client->assigned_to === $user->id
                || $teamMemberIds->contains($client->assigned_to);
        }

        if ($user->hasRole('sales_rep')) {
            return $client->assigned_to === $user->id;
        }

        return false;
    }
}
