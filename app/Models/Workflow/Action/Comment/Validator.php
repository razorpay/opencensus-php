<?php

namespace RZP\Models\Workflow\Action\Comment;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::ACTION_ID   => 'required|string',
        Entity::ADMIN_ID    => 'required|string',
        Entity::COMMENT     => 'required'
    );
}
