<?php

namespace RZP\Models\Comment;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::COMMENT  => 'required|string',
    ];
}
