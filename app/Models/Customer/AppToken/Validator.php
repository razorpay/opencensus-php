<?php

namespace RZP\Models\Customer\AppToken;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::DEVICE_TOKEN    => 'sometimes|string|max:50',
    ];
}
