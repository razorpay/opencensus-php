<?php

namespace RZP\Models\Card;

use RZP\Models\Payment;
use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::IIN             => 'sometimes|integer|digits:6',
            Entity::LAST4           => 'sometimes|string|digits:4',
            Entity::MERCHANT_ID     => 'sometimes|alpha_num',
            Entity::NETWORK         => 'sometimes|alpha_space',
            Entity::INTERNATIONAL   => 'sometimes|in:0,1',
            Payment\Entity::STATUS  => 'sometimes|string',
            Entity::EXPIRY_MONTH    => 'sometimes|integer|digits_between:1,2|max:12|min:1',
            Entity::EXPIRY_YEAR     => 'sometimes|integer|digits:4|non_past_year',
            Entity::VAULT_TOKEN     => 'sometimes|string',
            Entity::VAULT           => 'required_with:token|in:tokenex,rzpvault',
            Entity::GLOBAL_CARD_ID  => 'sometimes|alpha_num',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::IIN,
            Entity::LAST4,
            Entity::MERCHANT_ID,
            Entity::NETWORK,
            Entity::INTERNATIONAL,
            Payment\Entity::STATUS,
            Entity::EXPIRY_MONTH,
            Entity::EXPIRY_YEAR,
            Entity::VAULT_TOKEN,
            Entity::VAULT,
            Entity::GLOBAL_CARD_ID,
        ],
    ];
}
