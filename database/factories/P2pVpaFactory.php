<?php

namespace Database\Factories;

use RZP\Models\P2p\Vpa\Entity;
use RZP\Models\Merchant\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class P2pVpaFactory extends Factory
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
            Entity::ID               => Entity::generateUniqueId(),
            Entity::DEVICE_ID        => 'factory:' . \RZP\Models\P2p\Device\Entity::class,
            Entity::HANDLE           => fake()->randomElement(['rzpsharp', 'razorsharp']),
            Entity::GATEWAY_DATA     => fake()->randomElements(['a' => 1, 'b' => 2]),
            Entity::USERNAME         => fake()->word,
            Entity::BANK_ACCOUNT_ID  => 'factory:' . \RZP\Models\P2p\BankAccount\Entity::class,
            Entity::BENEFICIARY_NAME => fake()->name,
            Entity::FREQUENCY        => 'multiple',
            Entity::ACTIVE           => true,
            Entity::VALIDATED        => true,
            Entity::VERIFIED         => true,
            Entity::DEFAULT          => true,
            Entity::PERMISSIONS      => 0,
        ];
    }
}
