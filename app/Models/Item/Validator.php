<?php

namespace RZP\Models\Item;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME                => 'required|string',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::AMOUNT              => 'required|integer',
        Entity::CURRENCY            => 'required|size:3|in:INR',
    ];

    protected static $editRules  = [
        Entity::NAME                => 'sometimes|string',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::AMOUNT              => 'sometimes|integer',
        Entity::CURRENCY            => 'sometimes|size:3|in:INR',
    ];
}
