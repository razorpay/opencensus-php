<?php
namespace RZP\Models\AccessControlHistoryLogs;

use RZP\Constants;
use RZP\Base\Fetch as BaseFetch;

class Fetch extends BaseFetch
{

    const RULES = [
        self::DEFAULTS => [
            Entity::ENTITY_ID            => 'sometimes|alpha_num|size:14',
            Entity::ENTITY_TYPE          => 'sometimes|string',
            Entity::OWNER_TYPE           => 'sometimes|string',
            Entity::OWNER_ID             => 'sometimes|alpha_num|size:14',
        ],
    ];

    const ACCESSES = [
        self::DEFAULTS => [
            Entity::ENTITY_ID,
            Entity::ENTITY_TYPE,
            Entity::OWNER_ID,
            Entity::OWNER_TYPE,
        ],
    ];
}
