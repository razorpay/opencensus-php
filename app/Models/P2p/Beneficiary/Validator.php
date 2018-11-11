<?php

namespace Rzp\Models\P2p\Beneficiary;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected $createRules = [
        Entity::ID                       => 'required|string|regex:^(.*)$',
        Entity::ENTITY                   => 'required|string',
        Entity::BENEFICIARY_NAME         => 'required|string',
        Entity::ADDRESS                  => 'sometimes|string',
        Entity::USERNAME                 => 'sometimes|string',
        Entity::HANDLE                   => 'sometimes|string',
        Entity::MASKED_ACCOUNT_NUMBER    => 'sometimes|string',
        Entity::IFSC_CODE                => 'sometimes|string',
        Entity::BANK_NAME                => 'sometimes|string',
        Entity::CREATED_AT               => 'required|string',
    ];

    protected $validateRules = [
    ];

    protected $fetchAllRules = [
    ];
}
