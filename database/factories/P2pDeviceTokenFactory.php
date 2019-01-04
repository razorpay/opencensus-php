<?php


use Faker\Generator as Faker;
use RZP\Models\Merchant\Account;
use RZP\Models\P2p\Device\DeviceToken\Entity;
use RZP\Models\P2p\Device\DeviceToken\ClientLibrary;

$factory->define(Entity::class, function (Faker $faker) {
    return [
        Entity::ID              => Entity::generateUniqueId(),
        Entity::DEVICE_ID       => 'factory:' . \RZP\Models\P2p\Device\Entity::class,
        Entity::HANDLE          => $faker->randomElement(['rzpsharp', 'razorsharp']),
        Entity::GATEWAY_DATA    => $faker->randomElements(['a' => 1, 'b' => 2]),
        Entity::STATUS          => 'pending',
        Entity::CL              => [
            ClientLibrary::CAPABILITY   => $faker->numerify('#######################'),
            ClientLibrary::TOKEN        => $faker->lexify('???????????????????????????????'),
            ClientLibrary::PAYLOAD      => $faker->paragraph(4),
        ],
        Entity::REFRESHED_AT    => $faker->numerify('154222####'),
    ];
});
