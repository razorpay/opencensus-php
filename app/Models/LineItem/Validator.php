<?php

namespace RZP\Models\LineItem;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::QUANTITY            => 'sometimes|integer|min:1',
        Entity::ITEM_ID             => 'sometimes|string|max:19',
        Entity::NAME                => 'sometimes|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::AMOUNT              => 'sometimes|integer|min:100|max:50000000',
        Entity::CURRENCY            => 'sometimes|size:3|in:INR',
    ];

    protected static $createManyRules = [
        Entity::LINE_ITEMS          => 'required|array|min:1|max:10',
        Entity::LINE_ITEMS . '.*'   => 'required|array',
    ];

    protected static $editRules = [
        Entity::QUANTITY            => 'sometimes|integer|min:1',
        Entity::ITEM_ID             => 'sometimes|string|max:19',
        Entity::NAME                => 'sometimes|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::AMOUNT              => 'sometimes|integer|min:100|max:50000000',
        Entity::CURRENCY            => 'sometimes|size:3|in:INR',
    ];

    protected static $removeManyRules = [
        Entity::IDS                 => 'required|array|min:1|max:10',
    ];
}
