<?php

use Faker\Generator as Faker;
use RZP\Models\Merchant\Account;
use RZP\Models\P2p\Device\RegisterToken\Status;
use RZP\Models\P2p\Device\RegisterToken\Entity;

$factory->define(Entity::class, function (Faker $faker) {
    return [
        Entity::MERCHANT_ID     => Account::SHARED_ACCOUNT,
        Entity::HANDLE          => $faker->randomElement(['rzpaxis', 'razoraxis']),
        Entity::STATUS          => Status::PENDING,
    ];
});
