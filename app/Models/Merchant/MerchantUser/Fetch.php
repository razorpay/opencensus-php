<?php

namespace RZP\Models\Merchant\MerchantUser;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID => 'filled|string|size:14',
            Entity::USER_ID     => 'filled|string|size:14',
        ],
    ];

    const ACCESSES = [
        AuthType::ADMIN_AUTH    => [
            Entity::MERCHANT_ID,
            Entity::USER_ID,
        ],
    ];
}
