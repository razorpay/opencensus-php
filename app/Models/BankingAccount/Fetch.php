<?php

namespace RZP\Models\BankingAccount;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID           => 'sometimes|alpha_num',
            Entity::STATUS                => 'sometimes|string',
            Entity::ACCOUNT_NUMBER        => 'sometimes|alpha_num|max:40',
            Entity::CHANNEL               => 'sometimes|string',
            Entity::BANK_INTERNAL_STATUS  => 'sometimes|string',
            Entity::BALANCE_ID            => 'sometimes|alpha_num',
            Entity::BANK_REFERENCE_NUMBER => 'sometimes|string',
            Entity::FTS_FUND_ACCOUNT_ID   => 'sometimes|alpha_num',

        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::STATUS,
            Entity::ACCOUNT_NUMBER,
            Entity::CHANNEL,
            Entity::BANK_INTERNAL_STATUS,
            Entity::BALANCE_ID,
            Entity::BANK_REFERENCE_NUMBER,
            Entity::FTS_FUND_ACCOUNT_ID,
        ],
    ];
}
