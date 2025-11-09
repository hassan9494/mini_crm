<?php

namespace App\Providers;

use App\Domains\Clients\Models\Client;
use App\Domains\Clients\Policies\ClientPolicy;
use App\Domains\Communications\Models\Communication;
use App\Domains\Communications\Policies\CommunicationPolicy;
use App\Domains\FollowUps\Models\FollowUp;
use App\Domains\FollowUps\Policies\FollowUpPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Client::class => ClientPolicy::class,
        Communication::class => CommunicationPolicy::class,
        FollowUp::class => FollowUpPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Grant admin users full access to all gates
        Gate::before(static function ($user, $ability) {
            if ($user->hasRole('admin')) {
                return true;
            }

            return null;
        });
    }
}
