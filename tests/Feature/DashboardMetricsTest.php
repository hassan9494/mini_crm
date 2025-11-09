<?php

namespace Tests\Feature;

use App\Domains\Clients\Models\Client;
use App\Domains\Communications\Models\Communication;
use App\Domains\FollowUps\Models\FollowUp;
use Carbon\CarbonImmutable;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    public function test_manager_with_scope_can_view_metrics(): void
    {
        $now = CarbonImmutable::parse('2025-01-01 08:00:00');
        CarbonImmutable::setTestNow($now);

        $manager = $this->createUserWithRole('manager');
        $teamMember = $this->createUserWithRole('sales_rep', ['manager_id' => $manager->id]);

        $hotClient = Client::factory()->create([
            'status' => Client::STATUS_HOT,
            'assigned_to' => $manager->id,
        ]);

        $warmClient = Client::factory()->create([
            'status' => Client::STATUS_WARM,
            'assigned_to' => $teamMember->id,
        ]);

        FollowUp::factory()->for($hotClient, 'client')->for($manager, 'owner')->create([
            'due_date' => $now->addDay(),
        ]);

        FollowUp::factory()->for($warmClient, 'client')->for($teamMember, 'owner')->create([
            'due_date' => $now->subDay(),
        ]);

        Communication::factory()->for($hotClient, 'client')->state([
            'date' => $now->subDays(3),
        ])->create();

        Sanctum::actingAs($manager, ['dashboard:view', 'followups:manage']);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.totals.clients', 2)
            ->assertJsonPath('data.totals.follow_ups_pending', 2)
            ->assertJsonPath('data.totals.follow_ups_overdue', 1)
            ->assertJsonPath('data.totals.follow_ups_due_soon', 1)
            ->assertJsonPath('data.totals.communications_last_7_days', 1)
            ->assertJsonFragment([
                'clients_by_status' => [
                    Client::STATUS_HOT => 1,
                    Client::STATUS_WARM => 1,
                    Client::STATUS_INACTIVE => 0,
                ],
            ]);

        CarbonImmutable::setTestNow();
    }

    public function test_dashboard_requires_role(): void
    {
        // Manager with proper role can access dashboard
        $manager = $this->createUserWithRole('manager');
        Sanctum::actingAs($manager, ['dashboard:view']);

        $this->getJson('/api/v1/dashboard')->assertOk();

        // Sales rep cannot access dashboard (role restriction)
        $salesRep = $this->createUserWithRole('sales_rep', ['manager_id' => $manager->id]);
        Sanctum::actingAs($salesRep, ['dashboard:view']);

        $this->getJson('/api/v1/dashboard')->assertForbidden();
    }

    public function test_dashboard_cache_is_flushed_after_follow_up_mutation(): void
    {
        $now = CarbonImmutable::parse('2025-01-01 08:00:00');
        CarbonImmutable::setTestNow($now);

        $manager = $this->createUserWithRole('manager');
        Sanctum::actingAs($manager, ['dashboard:view', 'followups:manage']);

        $client = Client::factory()->create([
            'status' => Client::STATUS_HOT,
            'assigned_to' => $manager->id,
        ]);

        FollowUp::factory()->for($client, 'client')->for($manager, 'owner')->create([
            'due_date' => $now->addDay(),
        ]);

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.totals.follow_ups_pending', 1);

        FollowUp::factory()->for($client, 'client')->for($manager, 'owner')->create([
            'due_date' => $now->addDays(2),
        ]);

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.totals.follow_ups_pending', 1);

        $payload = [
            'due_date' => $now->addDays(3)->toDateTimeString(),
            'notes' => 'New follow-up via API',
        ];

        $this->postJson("/api/v1/clients/{$client->id}/follow-ups", $payload)
            ->assertCreated();

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.totals.follow_ups_pending', 3);

        CarbonImmutable::setTestNow();
    }
}
