<?php

namespace RZP\Models\Admin\Role;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME            => 'required|string|max:250',
        Entity::DESCRIPTION     => 'sometimes|string|max:255',
        Entity::PERMISSIONS     => 'sometimes',
    ];

    protected static $editRules = [
        Entity::NAME            => 'required|string|max:250',
        Entity::DESCRIPTION     => 'sometimes|string|max:255',
        Entity::PERMISSIONS     => 'sometimes',
    ];
}
