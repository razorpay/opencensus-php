<?php

namespace RZP\Models\Customer\App;

use RZP\Models\Base;
use RZP\Models\Customer\App\Entity;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::MERCHANT_ID     => 'required|string|max:14',
        Entity::CUSTOMER_ID     => 'required|string|max:14',
        Entity::DEVICE_TOKEN    => 'sometimes|string|max:50',
    );
}