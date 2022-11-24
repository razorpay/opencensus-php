<?php

namespace Database\Factories;

use RZP\Models\Merchant\Account;
use RZP\Models\P2p\Device\RegisterToken\Status;
use RZP\Models\P2p\Device\RegisterToken\Entity;
use Illuminate\Database\Eloquent\Factories\Factory;

class P2pRegisterTokenFactory extends Factory
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
            Entity::MERCHANT_ID     => Account::SHARED_ACCOUNT,
            Entity::HANDLE          => fake()->randomElement(['rzpaxis', 'razoraxis']),
            Entity::STATUS          => Status::PENDING,
        ];
    }
}
