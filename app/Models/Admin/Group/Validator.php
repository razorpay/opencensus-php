<?php

namespace RZP\Models\Admin\Group;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME        => 'required|string|max:100',
        Entity::DESCRIPTION => 'required|string|max:250',

    ];

    protected static $editRules = [
        Entity::NAME        => 'sometimes|string|max:100',
        Entity::DESCRIPTION => 'sometimes|string|max:250',
    ];
}
