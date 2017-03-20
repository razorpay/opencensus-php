<?php

namespace RZP\Models\Workflow\Action\Comment;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ACTION_ID   => 'required|string|max:14',
        Entity::ADMIN_ID    => 'required|string|max:14',
        Entity::COMMENT     => 'required|string',
    ];
}
