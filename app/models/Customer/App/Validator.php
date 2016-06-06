<?php

namespace Models\Customer\App;

use Models\Base;
use Models\Customer\App\Entity;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::DEVICE_ID       => 'required|string|max:50',
        Entity::MERCHANT_ID     => 'required|string|max:14',
        Entity::CUSTOMER_ID     => 'required|string|max:14',
    );
}