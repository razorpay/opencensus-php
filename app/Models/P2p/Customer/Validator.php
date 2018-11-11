<?php

namespace Rzp\Models\P2p\Customer;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected $createRules = [
        Entity::ID           => 'required|string|regex:^cust_\w{14}$',
        Entity::CONTACT      => 'required|string|regex:^\+91(\d*){10}$',
        Entity::EMAIL        => 'required|string',
        Entity::ACTIVE       => 'required|string',
        Entity::NOTES        => 'required|array',
        Entity::CREATED_AT   => 'required|string',
    ];

    protected $startVerificationRules = [
    ];

    protected $getVerificationStatusRules = [
    ];

    protected $deleteRules = [
    ];
}
