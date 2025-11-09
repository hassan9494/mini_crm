<?php

namespace Database\Factories;

use App\Domains\Clients\Models\Client;
use App\Domains\Communications\Models\Communication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Communication>
 */
class CommunicationFactory extends Factory
{
    protected $model = Communication::class;

    public function definition(): array
    {
        $types = [
            Communication::TYPE_CALL,
            Communication::TYPE_EMAIL,
            Communication::TYPE_MEETING,
        ];

        return [
            'client_id' => Client::factory(),
            'type' => $this->faker->randomElement($types),
            'date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
