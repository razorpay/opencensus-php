<?php

namespace Database\Factories;

use RZP\Models\P2p\BankAccount\Bank\Entity;
use Illuminate\Database\Eloquent\Factories\Factory;

class P2PBankFactory extends Factory
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
            Entity::NAME            => fake()->name,
            Entity::HANDLE          => fake()->randomElement(['rzpsharp', 'razorsharp']),
            Entity::GATEWAY_DATA    => fake()->randomElements(['a' => 1, 'b' => 2]),
            Entity::IFSC            => strtoupper(fake()->lexify('????')),
            Entity::UPI_IIN         => fake()->numerify('91####'),
            Entity::UPI_FORMAT      => fake()->randomElement(['FORMAT1', 'FORMAT2']),
            Entity::ACTIVE          => true,
            Entity::REFRESHED_AT    => fake()->numerify('154222####'),
        ];
    }
}
