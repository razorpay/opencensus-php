<?php

namespace RZP\Models\Merchant\MerchantNotificationConfig;

use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Base;

class Fetch extends Base\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                => 'sometimes|string|min:14|max:18',
            Entity::MERCHANT_ID       => 'sometimes|string|size:14',
            Entity::NOTIFICATION_TYPE => 'sometimes|string|max:100|in:bene_bank_downtime,fund_loading_downtime',
            Entity::CONFIG_STATUS     => 'sometimes|string|in:enabled,disabled',

        ],
    ];

    const ACCESSES = [
        AuthType::ADMIN_AUTH => [
            Entity::ID,
            Entity::MERCHANT_ID,
            Entity::NOTIFICATION_TYPE,
            Entity::CONFIG_STATUS,
        ],
    ];
}
