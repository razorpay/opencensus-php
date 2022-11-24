<?php

namespace Database\Factories;

use RZP\Models\P2p\BankAccount\Entity;
use RZP\Models\P2p\BankAccount\Credentials;
use RZP\Models\P2p\BankAccount\Type;
use Illuminate\Database\Eloquent\Factories\Factory;

class P2pBankAccountFactory extends Factory
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
            Entity::ID                    => Entity::generateUniqueId(),
            Entity::DEVICE_ID             => 'factory:' . \RZP\Models\P2p\Device\Entity::class,
            Entity::HANDLE                => fake()->randomElement(['rzpsharp', 'razorsharp']),
            Entity::GATEWAY_DATA          => fake()->randomElements(['a' => 1, 'b' => 2]),
            Entity::BANK_ID               => fake()->randomElement(['ARZP', 'BRZP', 'CRZP']),
            Entity::IFSC                  => fake()->lexify('RZP????????'),
            Entity::ACCOUNT_NUMBER        => fake()->numerify('###########5555'),
            Entity::MASKED_ACCOUNT_NUMBER => fake()->numerify('*********#5555'),
            Entity::BENEFICIARY_NAME      => fake()->name,
            Entity::CREDS                 => [
                [
                    Credentials::TYPE           => 'pin',
                    Credentials::SUB_TYPE       => 'upipin',
                    Credentials::SET            => true,
                    Credentials::FORMAT         => fake()->randomElement(['NUM', 'ALPHANUM']),
                    Credentials::LENGTH         => fake()->randomElement([4, 6])
                ],
            ],
            Entity::TYPE                    => fake()->randomElement(Type::BANK_ACCOUNT_TYPES),
            Entity::REFRESHED_AT            => fake()->numerify('154222####'),
        ];
    }
}
