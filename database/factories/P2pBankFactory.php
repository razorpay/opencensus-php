<?php

use Faker\Generator as Faker;
use RZP\Models\Merchant\Account;
use RZP\Models\P2p\BankAccount\Bank\Entity;

$factory->define(Entity::class, function (Faker $faker) {
    return [
        Entity::NAME            => $faker->name,
        Entity::HANDLE          => $faker->randomElement(['rzpsharp', 'razorsharp']),
        Entity::GATEWAY_DATA    => $faker->randomElements(['a' => 1, 'b' => 2]),
        Entity::IFSC            => strtoupper($faker->lexify('????')),
        Entity::UPI_IIN         => $faker->numerify('91####'),
        Entity::UPI_FORMAT      => $faker->randomElement(['FORMAT1', 'FORMAT2']),
        Entity::ACTIVE          => true,
        Entity::REFRESHED_AT    => $faker->numerify('154222####'),
    ];
});
