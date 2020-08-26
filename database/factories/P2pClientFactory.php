<?php

use Faker\Generator as Faker;
use RZP\Models\Merchant\Account;
use RZP\Models\P2p\Client\Entity;
use RZP\Models\P2p\Client\Config;
use RZP\Models\P2p\Client\Secrets;
use RZP\Models\P2p\Client\GatewayData;

$factory->define(Entity::class, function (Faker $faker) {
    return [
        Entity::HANDLE          => $faker->randomElement(['rzpsharp', 'razorsharp']),
        Entity::CLIENT_TYPE     => 'merchant',
        Entity::CLIENT_ID       => Account::TEST_ACCOUNT,
        Entity::GATEWAY_DATA    => [
        ],
        Entity::SECRETS         => [
        ],
        Entity::CONFIG          => [
            Config::MAX_VPA     => 5,
        ],
    ];
});
