<?php

use Faker\Generator as Faker;
use RZP\Models\Merchant\Account;
use RZP\Models\P2p\BankAccount\Bank\Entity;

$factory->define(Entity::class, function (Faker $faker) {
    return [
        Entity::IFSC            => strtoupper($faker->lexify('????')),
        Entity::NAME            => $faker->name,
        Entity::UPI_IIN         => $faker->numerify('91####'),
        Entity::UPI_FORMAT      => $faker->randomElement(['FORMAT1', 'FORMAT2']),
        Entity::ACTIVE          => true,
        Entity::SPOC            => ['name' => $faker->name, 'email' => $faker->email],
        Entity::REFRESHED_AT    => $faker->numerify('154222####'),
    ];
});
