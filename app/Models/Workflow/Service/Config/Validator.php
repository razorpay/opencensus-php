<?php

namespace RZP\Models\Workflow\Service\Config;

use RZP\Base;

class Validator extends Base\Validator
{
    const CREATE = 'create';

    const UPDATE = 'update';

    protected static $createRules = [
        Entity::OWNER_ID             => 'required|string|max:14',
        Entity::OWNER_TYPE           => 'required|string|max:20',
        Entity::TYPE                 => 'required|string|max:20',
        Entity::ORG_ID               => 'required|string|max:14',
        Entity::ENABLED              => 'required',
    ];

    protected static $updateRules = [
        Entity::ID                   => 'required|string|max:14',
        Entity::OWNER_ID             => 'required|string|max:14',
        Entity::OWNER_TYPE           => 'required|string|max:20',
        Entity::TYPE                 => 'required|string|max:20',
        Entity::ORG_ID               => 'required|string|max:14',
        Entity::ENABLED              => 'required',
    ];
}
