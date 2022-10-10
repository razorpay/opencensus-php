<?php

namespace RZP\Models\Checkout\Order;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    public const RULES = [
        self::DEFAULTS => [
            Entity::CHECKOUT_ID => 'sometimes|string',
            Entity::INVOICE_ID  => 'sometimes|string',
            Entity::MERCHANT_ID => 'sometimes|string',
            Entity::ORDER_ID    => 'sometimes|string',
            Entity::CONTACT     => 'sometimes|string',
            Entity::EMAIL       => 'sometimes|string',
            Entity::CLOSE_REASON => 'sometimes|string',
            Entity::STATUS       => 'sometimes|string', 
        ],
    ];

    public const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::CHECKOUT_ID,
            Entity::INVOICE_ID,
            Entity::MERCHANT_ID,
            Entity::ORDER_ID,
            Entity::CONTACT,
            Entity::EMAIL,
            Entity::CLOSE_REASON,
            Entity::STATUS,
        ],
    ];
}
