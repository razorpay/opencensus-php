<?php

namespace RZP\Models\Workflow\Service\EntityMap;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::WORKFLOW_ID          => 'required|string|max:14',
        Entity::CONFIG_ID            => 'required|string|max:14',
        Entity::ENTITY_ID            => 'required|string|max:14',
        Entity::ENTITY_TYPE          => 'required|string|max:255',
    ];
}
