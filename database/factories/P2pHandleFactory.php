<?php

namespace Database\Factories;

use RZP\Models\Merchant\Account;
use RZP\Models\P2p\Vpa\Handle\Entity;
use Illuminate\Database\Eloquent\Factories\Factory;

class P2pHandleFactory extends Factory
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
            Entity::CODE            => fake()->randomElement(['rzpsharp', 'razorsharp']),
            Entity::MERCHANT_ID     => Account::SHARED_ACCOUNT,
            Entity::BANK            => fake()->randomElement(['ARZP', 'BRZP']),
            Entity::ACQUIRER        => 'p2p_upi_sharp',
            Entity::ACTIVE          => true,
        ];
    }
}
