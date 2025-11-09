<?php

namespace Database\Factories;

use App\Domains\Clients\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        $statuses = [Client::STATUS_HOT, Client::STATUS_WARM, Client::STATUS_INACTIVE];

        return [
            'name' => $this->faker->company(),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'status' => $this->faker->randomElement($statuses),
            'assigned_to' => null,
            'last_communication_date' => $this->faker->optional()->dateTimeBetween('-30 days'),
        ];
    }
}
