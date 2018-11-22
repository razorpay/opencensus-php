<?php

use Faker\Generator as Faker;
use RZP\Models\Customer\Entity;
use RZP\Models\Merchant\Account;

$factory->define(Entity::class, function (Faker $faker) {
    return [
        Entity::ID                    => Entity::generateUniqueId(),
        Entity::MERCHANT_ID           => Account::TEST_ACCOUNT,
        Entity::NAME                  => $faker->name,
        Entity::CONTACT               => $faker->numerify('+919#########'),
        Entity::EMAIL                 => $faker->email,
        Entity::NOTES                 => [],
        Entity::ACTIVE                => true
    ];
});
