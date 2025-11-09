<?php

namespace Database\Factories;

use App\Domains\Clients\Models\Client;
use App\Domains\FollowUps\Models\FollowUp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FollowUp>
 */
class FollowUpFactory extends Factory
{
    protected $model = FollowUp::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'user_id' => User::factory(),
            'due_date' => $this->faker->dateTimeBetween('-1 week', '+2 weeks'),
            'notes' => $this->faker->optional()->sentence(),
            'status' => FollowUp::STATUS_PENDING,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => FollowUp::STATUS_COMPLETED,
        ]);
    }
}
