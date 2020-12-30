<?php

namespace RZP\Models\Application;

use RZP\Base;

class Validator extends Base\Validator
{
    const BEFORE_CREATE_APP = 'before_create_app';

    const BEFORE_UPDATE_APP = 'before_update_app';

    protected static $beforeCreateAppRules = [
        Entity::NAME                 => 'required|string|max:255',
        Entity::TITLE                => 'required|string|max:255',
        Entity::TYPE                 => 'required|string|max:255',
        Entity::DESCRIPTION          => 'sometimes|string',
        Entity::HOME_APP             => 'required|boolean',
    ];

    protected static $beforeUpdateAppRules = [
        Entity::ID                   => 'required|string|max:14',
        Entity::NAME                 => 'sometimes|string|max:255',
        Entity::TITLE                => 'sometimes|string|max:255',
        Entity::TYPE                 => 'sometimes|string|max:255',
        Entity::DESCRIPTION          => 'sometimes|string',
        Entity::HOME_APP             => 'sometimes|boolean',
    ];

    protected static $createRules = [
        Entity::NAME                 => 'required|string|max:255',
        Entity::TITLE                => 'required|string|max:255',
        Entity::TYPE                 => 'required|string|max:255',
        Entity::DESCRIPTION          => 'sometimes|string',
        Entity::HOME_APP             => 'required|boolean',
    ];

    protected static $editRules = [
        Entity::ID                   => 'required|string|max:14',
        Entity::NAME                 => 'sometimes|string|max:255',
        Entity::TITLE                => 'sometimes|string|max:255',
        Entity::TYPE                 => 'sometimes|string|max:255',
        Entity::DESCRIPTION          => 'sometimes|string',
        Entity::HOME_APP             => 'sometimes|boolean',
    ];
}
