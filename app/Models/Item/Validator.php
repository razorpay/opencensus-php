<?php

namespace RZP\Models\Item;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        // Entity::ACTIVE              => 'sometimes|boolean',
        Entity::NAME                => 'required|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::AMOUNT              => 'required|integer|min:100|max:50000000',
        Entity::CURRENCY            => 'required|size:3|in:INR',
    ];

    protected static $editRules  = [
        Entity::NAME                => 'sometimes|string',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::AMOUNT              => 'sometimes|integer',
        Entity::CURRENCY            => 'sometimes|size:3|in:INR',
    ];
}
