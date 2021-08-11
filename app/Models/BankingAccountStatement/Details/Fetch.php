<?php

namespace RZP\Models\BankingAccountStatement\Details;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID    => 'sometimes|unsigned_id',
            Entity::BALANCE_ID     => 'sometimes|unsigned_id',
            Entity::ACCOUNT_NUMBER => 'sometimes|alpha_num|max:40',
            Entity::CHANNEL        => 'sometimes|string|custom',
            Entity::STATUS         => 'sometimes|string|custom',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::BALANCE_ID,
            Entity::ACCOUNT_NUMBER,
            Entity::CHANNEL,
            Entity::STATUS,
        ],
    ];

    public function validateChannel(string $attribute, string $channel)
    {
        Channel::validate($channel);
    }

    public function validateStatus(string $attribute, string $status)
    {
        Status::validate($status);
    }
}
