<?php

namespace RZP\Models\Admin\Org;

use RZP\Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::DISPLAY_NAME  => 'required|string|max:250',
        Entity::BUSINESS_NAME => 'required|string|max:250',
        Entity::EMAIL         => 'required|email',
        Entity::EMAIL_DOMAINS => 'required|string|max:500',
        Entity::AUTH          => 'required|string|max:250',
        Entity::LOGO          => 'sometimes|url',
    ];

}
