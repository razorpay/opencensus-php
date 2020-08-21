<?php

namespace RZP\Models\BankAccount;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID      => 'sometimes|alpha_num',
            Entity::TYPE             => 'sometimes|in:customer,merchant',
            Entity::ENTITY_ID        => 'sometimes|alpha_num',
            Entity::BENEFICIARY_CODE => 'sometimes|alpha_num',
            Entity::IFSC_CODE        => 'sometimes|alpha_num|size:11',
            Entity::ACCOUNT_NUMBER   => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::TYPE,
            Entity::ENTITY_ID,
            Entity::BENEFICIARY_CODE,
            Entity::IFSC_CODE,
            Entity::ACCOUNT_NUMBER,
        ],
    ];
}
