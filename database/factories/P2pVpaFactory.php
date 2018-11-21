<?php

use Faker\Generator as Faker;
use RZP\Models\P2p\Vpa\Entity;
use RZP\Models\Merchant\Account;

$factory->define(Entity::class, function (Faker $faker) {
    return [
        Entity::ID               => Entity::generateUniqueId(),
        Entity::DEVICE_ID        => 'factory:' . \RZP\Models\P2p\Device\Entity::class,
        Entity::HANDLE           => $faker->randomElement(['rzpsharp', 'razorsharp']),
        Entity::GATEWAY_DATA     => $faker->randomElements(['a' => 1, 'b' => 2]),
        Entity::USERNAME         => $faker->word,
        Entity::BANK_ACCOUNT_ID  => 'factory:' . \RZP\Models\P2p\BankAccount\Entity::class,
        Entity::BENEFICIARY_NAME => $faker->name,
        Entity::FREQUENCY        => 'multiple',
        Entity::ACTIVE           => true,
        Entity::VALIDATED        => true,
        Entity::VERIFIED         => true,
        Entity::DEFAULT          => true,
        Entity::PERMISSIONS      => 0,
    ];
});
