<?php


use Faker\Generator as Faker;
use RZP\Models\Merchant\Account;
use RZP\Models\P2p\Base\Upi\ClientLibrary;
use RZP\Models\P2p\Device\DeviceToken\Entity;

$factory->define(Entity::class, function (Faker $faker) {
    return [
        Entity::ID              => Entity::generateUniqueId(),
        Entity::DEVICE_ID       => 'factory:' . \RZP\Models\P2p\Device\Entity::class,
        Entity::HANDLE          => $faker->randomElement(['rzpsharp', 'razorsharp']),
        Entity::GATEWAY_DATA    => $faker->randomElements(['a' => 1, 'b' => 2]),
        Entity::STATUS          => 'pending',
        Entity::REFRESHED_AT    => $faker->numerify('154222####'),
    ];
});
