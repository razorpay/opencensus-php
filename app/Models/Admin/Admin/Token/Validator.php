<?php

namespace RZP\Models\Admin\Admin\Token;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::TOKEN       => 'required|string|min:20',
        Entity::EXPIRES_AT  => 'required|numeric',
    ];
}
