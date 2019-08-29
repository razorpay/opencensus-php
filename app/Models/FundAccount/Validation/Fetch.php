<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Base;
use RZP\Http\BasicAuth\Type as AuthType;

/**
 * Class Fetch
 *
 * @package RZP\Models\FundAccount\Validation
 */
class Fetch extends Base\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::FUND_ACCOUNT_ID     => 'sometimes|string',
            Entity::MERCHANT_ID         => 'sometimes|string',
            Entity::STATUS              => 'sometimes|string',
            Entity::BALANCE_ID          => 'sometimes|unsigned_id',
        ],
    ];

    const SIGNED_IDS = [
        Entity::FUND_ACCOUNT_ID,
        Entity::MERCHANT_ID,
    ];

    const ACCESSES = [
        self::DEFAULTS           => [
            Entity::STATUS,
            Entity::BALANCE_ID,
        ],
        AuthType::ADMIN_AUTH => [
            Entity::FUND_ACCOUNT_ID,
            Entity::MERCHANT_ID,
        ],
    ];
}
