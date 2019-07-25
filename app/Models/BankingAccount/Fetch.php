<?php

namespace RZP\Models\BankingAccount;

use RZP\Http\BasicAuth;
use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID           => 'sometimes|unsigned_id',
            Entity::STATUS                => 'sometimes|string|custom',
            Entity::ACCOUNT_NUMBER        => 'sometimes|alpha_num|max:40',
            Entity::CHANNEL               => 'sometimes|string|custom',
            Entity::BANK_INTERNAL_STATUS  => 'sometimes|string',
            Entity::BALANCE_ID            => 'sometimes|unsigned_id',
            Entity::BANK_REFERENCE_NUMBER => 'sometimes|string',
            Entity::FTS_FUND_ACCOUNT_ID   => 'sometimes|unsigned_id',
        ],
        BasicAuth\Type::PRIVILEGE_AUTH => [
            self::EXPAND . '.*'           => 'filled|string|in:merchant,merchant.merchantDetail',
            Entity::MERCHANT_ID           => 'sometimes|unsigned_id',
        ]
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
            self::EXPAND . '.*',
        ],
    ];

    const COMMON_FIELDS = [
        Entity::MERCHANT_ID,
        Entity::STATUS,
        Entity::ACCOUNT_NUMBER,
        Entity::CHANNEL,
        Entity::BANK_INTERNAL_STATUS,
        Entity::BALANCE_ID,
        Entity::BANK_REFERENCE_NUMBER,
        Entity::FTS_FUND_ACCOUNT_ID,
    ];

    public function validateStatus(string $attribute, string $status)
    {
        Status::validate($status);
    }

    public function validateChannel(string $attribute, string $channel)
    {
        Channel::validateChannel($channel);
    }
}
