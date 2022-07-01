<?php

namespace RZP\Models\AccessControlPrivileges;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME            => 'required|string',
        Entity::LABEL           => 'required|string',
        Entity::DESCRIPTION     => 'sometimes|string',
        Entity::PARENT_ID       => 'sometimes|string|size:14',
        Entity::VISIBILITY      => 'sometimes|int',
        Entity::EXTRA_DATA      => 'sometimes|array',
        Entity::VIEW_POSITION   => 'required|int',
    ];
}
