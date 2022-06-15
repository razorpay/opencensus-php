<?php

namespace RZP\Models\AccessControlHistoryLogs;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ENTITY_TYPE           => 'required|string',
        Entity::ENTITY_ID             => 'required|alpha_num|size:14',
        Entity::MESSAGE               => 'sometimes|string',
        Entity::PREVIOUS_VALUE        => 'sometimes|array',
        Entity::NEW_VALUE             => 'sometimes|array',
        Entity::OWNER_TYPE            => 'sometimes|string',
        Entity::OWNER_ID              => 'sometimes|alpha_num|size:14',
        Entity::CREATED_BY            => 'required|string',
    ];
}
