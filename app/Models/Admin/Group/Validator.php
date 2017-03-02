<?php

namespace RZP\Models\Admin\Group;

use RZP\Models\Admin\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME        => 'required|string|max:255',
        Entity::DESCRIPTION => 'required|string|max:255',
        'sub_groups'        => 'sometimes|array',
        'admins'            => 'sometimes|array',
        'merchants'         => 'sometimes|array',
        'roles'             => 'sometimes|array',
        'parents'           => 'sometimes|array',
    ];

    protected static $editRules = [
        Entity::NAME        => 'sometimes|string|max:255',
        Entity::DESCRIPTION => 'sometimes|string|max:255',
        'sub_groups'        => 'sometimes|array',
        'admins'            => 'sometimes|array',
        'merchants'         => 'sometimes|array',
        'roles'             => 'sometimes|array',
        'parents'           => 'sometimes|array',
    ];

    public $isOrgSpecificValidationSupported = false;
}
