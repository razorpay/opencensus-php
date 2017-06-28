<?php

namespace RZP\Models\Customer\GatewayToken;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::RECURRING       => 'sometimes|boolean',
        Entity::ACCESS_TOKEN    => 'sometimes|alpha_num|nullable',
        Entity::REFRESH_TOKEN   => 'sometimes|alpha_num|nullable',
    );
}
