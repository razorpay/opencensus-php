<?php

namespace RZP\Models\Admin\Permission;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME            => 'required|string|max:250',
        Entity::DESCRIPTION     => 'sometimes|string|max:255',
    ];

    protected static $editRules = [
        Entity::NAME            => 'required|string|max:250',
        Entity::DESCRIPTION     => 'sometimes|string|max:255',
    ];
}
