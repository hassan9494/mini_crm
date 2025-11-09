<?php

namespace Tests\Feature;

use App\Domains\Clients\Models\Client;
use App\Domains\FollowUps\Models\FollowUp;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use App\Domains\FollowUps\Notifications\FollowUpDueNotification;
use Tests\TestCase;

class FollowUpManagementTest extends TestCase
{
    public function test_manager_can_create_follow_up_for_assigned_client(): void
    {
        Notification::fake();
        $now = Carbon::parse('2025-02-01 09:00:00');
        Carbon::setTestNow($now);

        $manager = $this->createUserWithRole('manager');
        Sanctum::actingAs($manager, ['followups:manage']);

        $client = Client::factory()->create([
            'assigned_to' => $manager->id,
            'status' => Client::STATUS_HOT,
        ]);

        $payload = [
            'due_date' => $now->addDays(2)->toDateTimeString(),
            'notes' => 'Follow up on proposal',
        ];

        $response = $this->postJson("/api/v1/clients/{$client->id}/follow-ups", $payload);

        $response->assertCreated()
            ->assertJsonPath('data.user_id', $manager->id)
            ->assertJsonPath('data.notes', 'Follow up on proposal')
            ->assertJsonPath('data.status', FollowUp::STATUS_PENDING);

        $this->assertDatabaseHas('follow_ups', [
            'client_id' => $client->id,
            'user_id' => $manager->id,
            'notes' => 'Follow up on proposal',
        ]);

        Notification::assertNothingSent();
        Carbon::setTestNow();
    }

    public function test_sales_rep_cannot_create_follow_up_for_unassigned_client(): void
    {
        $manager = $this->createUserWithRole('manager');
        $otherManager = $this->createUserWithRole('manager');
        $salesRep = $this->createUserWithRole('sales_rep', ['manager_id' => $manager->id]);

        $client = Client::factory()->create([
            'assigned_to' => $otherManager->id,
        ]);

        Sanctum::actingAs($salesRep, ['followups:manage']);

        $payload = [
            'due_date' => now()->addDay()->toDateTimeString(),
            'notes' => 'Attempt unauthorized follow-up',
        ];

        $this->postJson("/api/v1/clients/{$client->id}/follow-ups", $payload)
            ->assertForbidden();
    }

    public function test_follow_up_update_respects_authorization(): void
    {
        $manager = $this->createUserWithRole('manager');
        $salesRep = $this->createUserWithRole('sales_rep', ['manager_id' => $manager->id]);
        $otherRep = $this->createUserWithRole('sales_rep');

        $client = Client::factory()->create([
            'assigned_to' => $salesRep->id,
        ]);

        $followUp = FollowUp::factory()->for($client, 'client')->for($salesRep, 'owner')->create([
            'notes' => 'Initial note',
            'due_date' => now()->addDay(),
        ]);

        Sanctum::actingAs($otherRep, ['followups:manage']);
        $this->putJson("/api/v1/clients/{$client->id}/follow-ups/{$followUp->id}", [
            'notes' => 'Unauthorized update',
        ])->assertForbidden();

        Sanctum::actingAs($salesRep, ['followups:manage']);
        $this->putJson("/api/v1/clients/{$client->id}/follow-ups/{$followUp->id}", [
            'notes' => 'Updated notes',
            'status' => FollowUp::STATUS_COMPLETED,
        ])->assertOk()
            ->assertJsonPath('data.notes', 'Updated notes')
            ->assertJsonPath('data.status', FollowUp::STATUS_COMPLETED);
    }
}
