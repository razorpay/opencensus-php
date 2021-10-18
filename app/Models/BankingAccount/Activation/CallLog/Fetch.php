<?php


namespace RZP\Models\BankingAccount\Activation\CallLog;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        AuthType::PRIVILEGE_AUTH => [
            self::EXPAND_EACH             => 'filled|string|in:admin,comment,state_log',
            Entity::BANKING_ACCOUNT_ID    => 'sometimes|unsigned_id',
            Entity::ADMIN_ID              => 'sometimes|unsigned_id',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::BANKING_ACCOUNT_ID,
            Entity::ADMIN_ID,
            self::EXPAND_EACH,
        ],
    ];
}
