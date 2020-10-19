<?php

namespace RZP\Models\Workflow\Service\StateMap;

use RZP\Base;

class Validator extends Base\Validator
{
    const CREATE = 'create';

    const UPDATE = 'update';

    protected static $createRules = [
        Entity::REQUEST_WORKFLOW_ID             => 'required|string|max:14',
        Entity::REQUEST_STATE_ID                => 'required|string|max:14',
        Entity::REQUEST_STATE_NAME              => 'required|string',
        Entity::REQUEST_STATUS                  => 'required|string',
        Entity::REQUEST_GROUP_NAME              => 'required|string',
        Entity::REQUEST_TYPE                    => 'required|string',
        Entity::REQUEST_RULES . "." . Entity::REQUEST_ACTOR_PROPERTY_KEY          => 'required|string',
        Entity::REQUEST_RULES . "." . Entity::REQUEST_ACTOR_PROPERTY_VALUE        => 'required|string',
    ];

    protected static $updateRules = [
        Entity::REQUEST_WORKFLOW_ID             => 'required|string|max:14',
        Entity::REQUEST_STATUS                  => 'required|string',
    ];
}
