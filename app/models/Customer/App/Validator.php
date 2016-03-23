<?php

namespace Models\Customer\App;

use Models\Base;
use Models\Customer\App\Entity;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::APP_ID          =>      'required',
        Entity::DEVICE_ID       =>      'required',
        Entity::MERCHANT_ID     =>      'required',
        Entity::CUSTOMER_ID     =>      'required',
    );
}