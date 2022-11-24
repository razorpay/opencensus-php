<?php

namespace Database\Factories;

use RZP\Models\Merchant\Account;
use RZP\Models\P2p\Client\Entity;
use RZP\Models\P2p\Client\Config;
use Illuminate\Database\Eloquent\Factories\Factory;

class P2pClientFactory extends Factory
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
            Entity::HANDLE          => fake()->randomElement(['rzpsharp', 'razorsharp']),
            Entity::CLIENT_TYPE     => 'merchant',
            Entity::CLIENT_ID       => Account::TEST_ACCOUNT,
            Entity::GATEWAY_DATA    => [
            ],
            Entity::SECRETS         => [
            ],
            Entity::CONFIG          => [
                Config::MAX_VPA     => 5,
            ],
        ];
    }
}
