<?php

use Faker\Generator as Faker;
use RZP\Models\Merchant\Account;
use RZP\Models\P2p\Vpa\Handle\Entity;

$factory->define(Entity::class, function (Faker $faker) {
    return [
        Entity::HANDLE          => $faker->randomElement(['rzpsharp', 'razorsharp']),
        Entity::MERCHANT_ID     => Account::SHARED_ACCOUNT,
        Entity::ACQUIRER        => $faker->word,
        Entity::ACTIVE          => true,
    ];
});
