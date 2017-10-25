<?php
namespace RZP\Models\BankAccount;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID      => 'sometimes|alpha_num',
            Repository::WITH_TRASHED => 'sometimes|in:0,1',
            Entity::TYPE             => 'sometimes|in:customer,merchant',
            Entity::ENTITY_ID        => 'sometimes|alpha_num'
        ]
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Repository::WITH_TRASHED,
            Entity::TYPE,
            Entity::ENTITY_ID,
        ]
    ];
}
