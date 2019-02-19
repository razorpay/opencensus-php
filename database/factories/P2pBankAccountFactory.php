<?php

use Faker\Generator as Faker;
use RZP\Models\Merchant\Account;
use RZP\Models\P2p\BankAccount\Entity;
use RZP\Models\P2p\BankAccount\Credentials;

$factory->define(Entity::class, function (Faker $faker) {
    return [
        Entity::ID                      => Entity::generateUniqueId(),
        Entity::DEVICE_ID               => 'factory:' . \RZP\Models\P2p\Device\Entity::class,
        Entity::HANDLE                  => $faker->randomElement(['rzpsharp', 'razorsharp']),
        Entity::GATEWAY_DATA            => $faker->randomElements(['a' => 1, 'b' => 2]),
        Entity::BANK                    => $faker->randomElement(['ARZP', 'BRZP', 'CRZP']),
        Entity::IFSC                    => $faker->lexify('RZP????????'),
        Entity::ACCOUNT_NUMBER          => $faker->numerify('###########5555'),
        Entity::MASKED_ACCOUNT_NUMBER   => $faker->numerify('*********#5555'),
        Entity::BENEFICIARY_NAME        => $faker->name,
        Entity::CREDS                   => [
            [
                Credentials::TYPE           => 'pin',
                Credentials::SUB_TYPE       => 'upipin',
                Credentials::SET            => true,
                Credentials::FORMAT         => $faker->randomElement(['NUM', 'ALPHANUM']),
                Credentials::LENGTH         => $faker->randomElement([4, 6])
            ],
        ],
        Entity::REFRESHED_AT            => $faker->numerify('154222####'),
    ];
});
