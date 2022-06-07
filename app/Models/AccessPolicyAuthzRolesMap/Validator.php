<?php

namespace RZP\Models\AccessPolicyAuthzRolesMap;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::PRIVILEGE_ID     => 'required|string|size:14',
        Entity::ACTION           => 'required|string',
        Entity::AUTHZ_ROLES      => 'sometimes|array',
        Entity::META_DATA        => 'sometimes|array',
    ];

    public static function isValidAction($action) :bool
    {
        return in_array($action, Entity::ACTION_TYPES);
    }

}
