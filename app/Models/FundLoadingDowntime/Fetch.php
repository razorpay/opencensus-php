<?php

namespace RZP\Models\FundLoadingDowntime;

use RZP\Base;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends Base\Fetch
{
    const RULES = [
        self::DEFAULTS       => [
            Entity::TYPE             => 'filled|string|max:255',
            Entity::SOURCE           => 'filled|string|max:255',
            Entity::CHANNEL          => 'filled|string|max:255',
            Entity::MODE             => 'filled|string|max:255',
            Entity::START_TIME       => 'filled|epoch',
            Entity::END_TIME         => 'sometimes|nullable|epoch',
            Entity::DOWNTIME_MESSAGE => 'sometimes|string',
            Entity::CREATED_BY       => 'filled|string|max:255',
        ],
        AuthType::ADMIN_AUTH => [
            Entity::ORG_ID => 'required|string|min:14|max:20',
        ],
    ];

    const ACCESSES = [
        AuthType::ADMIN_AUTH => [
            Entity::TYPE,
            Entity::SOURCE,
            Entity::CHANNEL,
            Entity::MODE,
            Entity::START_TIME,
            Entity::END_TIME,
            Entity::DOWNTIME_MESSAGE,
            Entity::CREATED_BY,
        ],
    ];
}
