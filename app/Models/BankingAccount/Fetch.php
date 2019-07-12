<?php

namespace RZP\Models\BankingAccount;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID           => 'sometimes|alpha_num',
            Entity::STATUS                => 'sometimes|string|custom',
            Entity::ACCOUNT_NUMBER        => 'sometimes|alpha_num|max:40',
            Entity::CHANNEL               => 'sometimes|string|custom',
            Entity::BANK_INTERNAL_STATUS  => 'sometimes|string|custom',
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

    const SIGNED_IDS = [
        Entity::MERCHANT_ID,
        Entity::BALANCE_ID,
        Entity::FTS_FUND_ACCOUNT_ID,
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

    public function validateBankInternalStatus(string $attribute, string $status)
    {
        Gateway\Rbl\Status::validate($status);
    }

    public function validateChannel(string $attribute, string $channel)
    {
        Channel::validateChannel($channel);
    }
}
