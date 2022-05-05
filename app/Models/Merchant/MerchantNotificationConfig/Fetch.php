<?php

namespace RZP\Models\Merchant\MerchantNotificationConfig;

use RZP\Base;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends Base\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                => 'sometimes|string|min:14|max:18',
            Entity::MERCHANT_ID       => 'sometimes|string|size:14',
            Entity::NOTIFICATION_TYPE => 'sometimes|string|max:100|custom',
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

    protected function validateNotificationType($attribute, $value)
    {
        Validator::validateNotificationType($attribute, $value);
    }
}
