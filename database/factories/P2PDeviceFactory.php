<?php

namespace Database\Factories;

use RZP\Models\Customer;
use RZP\Models\P2p\Device\Entity;
use RZP\Models\Merchant\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class P2PDeviceFactory extends Factory
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
            Entity::ID           => Entity::generateUniqueId(),
            Entity::CUSTOMER_ID  => 'factory:' . Customer\Entity::class,
            Entity::MERCHANT_ID  => Account::TEST_ACCOUNT,
            Entity::CONTACT      => fake()->numerify('+919#########'),
            Entity::SIMID        => fake()->numerify('SIMID#########'),
            Entity::UUID         => fake()->numerify('UUID##########'),
            Entity::TYPE         => 'mobile',
            Entity::OS           => fake()->randomElement(['android', 'macos']),
            Entity::OS_VERSION   => fake()->randomElement([7,8,9]),
            Entity::APP_NAME     => fake()->lexify('rzp.???????.com'),
            Entity::IP           => fake()->ipv4,
            Entity::GEOCODE      => fake()->latitude .','. fake()->longitude,
            Entity::AUTH_TOKEN   => fake()->word,
        ];
    }
}
