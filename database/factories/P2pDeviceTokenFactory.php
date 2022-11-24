<?php

namespace Database\Factories;

use RZP\Models\P2p\Device\DeviceToken\Entity;
use Illuminate\Database\Eloquent\Factories\Factory;

class P2pDeviceTokenFactory extends Factory
{

    protected $model = Entity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            Entity::ID              => Entity::generateUniqueId(),
            Entity::DEVICE_ID       => 'factory:' . \RZP\Models\P2p\Device\Entity::class,
            Entity::HANDLE          => fake()->randomElement(['rzpsharp', 'razorsharp']),
            Entity::GATEWAY_DATA    => fake()->randomElements(['a' => 1, 'b' => 2]),
            Entity::STATUS          => 'pending',
            Entity::REFRESHED_AT    => fake()->numerify('154222####'),
        ];
    }
}
