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
            Entity::ACCOUNT_TYPE          => 'sometimes|string',
            Entity::REVIEWER_ID           => 'sometimes|string',
        ],
        BasicAuth\Type::PRIVILEGE_AUTH => [
            self::EXPAND_EACH             => 'filled|string|in:merchant,merchant.merchantDetail,banking_account_details,reviewers',
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
            Entity::ACCOUNT_TYPE ,
            Entity::REVIEWER_ID,
            self::EXPAND_EACH,
        ],
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
