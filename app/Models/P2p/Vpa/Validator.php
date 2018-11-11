<?php

namespace Rzp\Models\P2p\Vpa;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected $createRules = [
        Entity::ID               => 'required|string|regex:^vpa_[\w]{14}$',
        Entity::ENTITY           => 'required|string|regex:^(.*)$',
        Entity::ADDRESS          => 'required|string|regex:^(.*)@(.*)$',
        Entity::USERNAME         => 'required|string|regex:^(.*)$',
        Entity::HANDLE           => 'required|string|regex:^(.*)$',
        Entity::BANK_ACCOUNT_ID  => 'required|string|regex:^ba_[\w]{14}$',
        Entity::CREATED_AT       => 'required|string',
    ];

    protected $fetchHandlesRules = [
    ];

    protected $fetchAllRules = [
    ];

    protected $fetchRules = [
    ];

    protected $assignBankAccountRules = [
    ];

    protected $checkAvailabilityRules = [
    ];

    protected $deleteRules = [
    ];
}
