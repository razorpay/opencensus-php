<?php

use Faker\Generator as Faker;
use RZP\Models\Merchant\Account;
use RZP\Models\P2p\Vpa\Handle\Entity;

$factory->define(Entity::class, function (Faker $faker) {
    return [
        Entity::CODE            => $faker->randomElement(['rzpsharp', 'razorsharp']),
        Entity::MERCHANT_ID     => Account::SHARED_ACCOUNT,
        Entity::BANK            => $faker->randomElement(['ARZP', 'BRZP']),
        Entity::ACQUIRER        => 'p2p_upi_sharp',
        Entity::ACTIVE          => true,
    ];
});
