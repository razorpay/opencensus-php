<?php

namespace Database\Factories;

use RZP\Models\Customer;
use RZP\Models\Merchant\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer\Entity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            Customer\Entity::ID                    => Customer\Entity::generateUniqueId(),
            Customer\Entity::MERCHANT_ID           => Account::TEST_ACCOUNT,
            Customer\Entity::NAME                  => fake()->name,
            Customer\Entity::CONTACT               => fake()->numerify('+919#########'),
            Customer\Entity::EMAIL                 => fake()->email,
            Customer\Entity::NOTES                 => [],
            Customer\Entity::ACTIVE                => true
        ];
    }
}
