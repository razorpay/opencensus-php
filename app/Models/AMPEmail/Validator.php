<?php

namespace RZP\Models\AMPEmail;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ENTITY_ID   => 'required|string|max:14',
        Entity::ENTITY_TYPE => 'required|string|in:merchant',
        Entity::VENDOR      => 'required|string|in:mailmodo',
        Entity::STATUS      => 'required|string|in:initiated,open,close,failed',
        Entity::TEMPLATE    => 'required|string|in:l1',
        Entity::METADATA    => 'sometimes|array',
    ];

    protected static $editRules   = [
        Entity::STATUS   => 'sometimes|string|in:open,close,failed',
        Entity::METADATA => 'sometimes|array',
    ];

}
