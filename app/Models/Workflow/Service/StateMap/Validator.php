<?php

namespace RZP\Models\Workflow\Service\StateMap;

use RZP\Base;

class Validator extends Base\Validator
{
    const CREATE_STATE = 'create_state';
    const UPDATE_STATE = 'update_state';

    protected static $createStateRules = [
        Entity::REQUEST_WORKFLOW_ID                                             => 'required|string|max:14',
        Entity::REQUEST_STATE_ID                                                => 'required|string|max:14',
        Entity::REQUEST_STATE_NAME                                              => 'required|string',
        Entity::REQUEST_STATUS                                                  => 'required|string',
        Entity::REQUEST_GROUP_NAME                                              => 'required|string',
        Entity::REQUEST_TYPE                                                    => 'required|string',
        Entity::REQUEST_RULES . "." . Entity::REQUEST_ACTOR_PROPERTY_KEY        => 'required|string',
        Entity::REQUEST_RULES . "." . Entity::REQUEST_ACTOR_PROPERTY_VALUE      => 'required|string',
    ];

    protected static $updateStateRules = [
        Entity::REQUEST_WORKFLOW_ID             => 'required|string|max:14',
        Entity::REQUEST_STATUS                  => 'required|string',
    ];

    protected static $createRules = [
        Entity::WORKFLOW_ID         => 'required|string|max:14',
        Entity::STATE_ID            => 'required|string|max:14',
        Entity::STATE_NAME          => 'required|string|max:255',
        Entity::TYPE                => 'required|string',
        Entity::GROUP_NAME          => 'required|string',
        Entity::STATUS              => 'required|string',
        Entity::ACTOR_TYPE_KEY      => 'required|string|max:255',
        Entity::ACTOR_TYPE_VALUE    => 'required|string|max:255',
    ];
}
