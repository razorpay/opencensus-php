<?php

namespace RZP\Models\Promotion\Event;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME            => 'required|string|regex:/^[a-zA-Z0-9][a-zA-Z0-9-\s]+/|max:255',
        Entity::DESCRIPTION     => 'filled|string|regex:/^[a-zA-Z0-9][a-zA-Z0-9-\s]+/|max:255'
    ];
}
