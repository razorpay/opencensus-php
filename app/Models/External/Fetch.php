<?php

namespace RZP\Models\External;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Models\BankingAccountStatement as BAS;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID           => 'sometimes|unsigned_id',
            Entity::TRANSACTION_ID        => 'sometimes|unsigned_id',
            Entity::CHANNEL               => 'sometimes|string|custom',
            Entity::BANK_REFERENCE_NUMBER => 'sometimes|string',
            Entity::UTR                   => 'sometimes|alpha_num'
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::TRANSACTION_ID,
            Entity::CHANNEL,
            Entity::BANK_REFERENCE_NUMBER,
            Entity::UTR,
        ],
    ];

    const COMMON_FIELDS = [
        Entity::MERCHANT_ID,
        Entity::TRANSACTION_ID,
        Entity::CHANNEL,
        Entity::BANK_REFERENCE_NUMBER,
        Entity::UTR,
    ];

    public function validateChannel(string $attribute, string $channel)
    {
        BAS\Channel::validate($channel);
    }
}
