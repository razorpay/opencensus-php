<?php

namespace RZP\Models\Customer\AppToken;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::MERCHANT_ID     => 'required|string|max:14',
        Entity::CUSTOMER_ID     => 'required|string|max:14',
        Entity::DEVICE_TOKEN    => 'sometimes|string|max:50',
    );
}
