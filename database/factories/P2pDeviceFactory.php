<?php

use Faker\Generator as Faker;
use RZP\Models\Merchant\Account;
use RZP\Models\P2p\Device\Entity;

$factory->define(Entity::class, function (Faker $faker) {
    return [
        Entity::ID           => Entity::generateUniqueId(),
        Entity::CUSTOMER_ID  => 'factory:' . RZP\Models\Customer\Entity::class,
        Entity::MERCHANT_ID  => Account::TEST_ACCOUNT,
        Entity::CONTACT      => $faker->numerify('+919#########'),
        Entity::SIMID        => $faker->numerify('SIMID#########'),
        Entity::UUID         => $faker->numerify('UUID##########'),
        Entity::TYPE         => 'mobile',
        Entity::OS           => $faker->randomElement(['android', 'macos']),
        Entity::OS_VERSION   => $faker->randomElement([7,8,9]),
        Entity::APP_NAME     => $faker->lexify('rzp.???????.com'),
        Entity::IP           => $faker->ipv4,
        Entity::GEOCODE      => $faker->latitude .','. $faker->longitude,
        Entity::AUTH_TOKEN   => $faker->word,
    ];
});
