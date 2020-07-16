<?php

namespace RZP\Models\PayoutDowntime;

use RZP\Base;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends Base\Fetch
{
    const RULES = [
        self::DEFAULTS       => [
            Entity::STATUS                => 'filled|string|max:255',
            Entity::CHANNEL               => 'filled|string|max:255',
            Entity::START_TIME            => 'filled|epoch',
            Entity::END_TIME              => 'filled|epoch',
            Entity::DOWNTIME_MESSAGE      => 'filled|string',
            Entity::UPTIME_MESSAGE        => 'sometimes|string',
            Entity::ENABLED_EMAIL_OPTION  => 'sometimes|string|max:30',
            Entity::DISABLED_EMAIL_OPTION => 'sometimes|string|max:30',
            Entity::ENABLED_EMAIL_STATUS  => 'sometimes|string|max:30',
            Entity::DISABLED_EMAIL_STATUS => 'sometimes|string|max:30',
            Entity::CREATED_BY            => 'filled|string|max:255',
        ],
        AuthType::ADMIN_AUTH => [
            Entity::ORG_ID => 'required|string|min:14|max:20',
        ],
    ];

    const ACCESSES = [
        AuthType::ADMIN_AUTH => [
            Entity::STATUS,
            Entity::CHANNEL,
            Entity::START_TIME,
            Entity::END_TIME,
            Entity::DOWNTIME_MESSAGE,
            Entity::UPTIME_MESSAGE,
            Entity::ENABLED_EMAIL_OPTION,
            Entity::DISABLED_EMAIL_OPTION,
            Entity::ENABLED_EMAIL_STATUS,
            Entity::DISABLED_EMAIL_STATUS,
            Entity::CREATED_BY,
        ],
    ];

}
