<?php

namespace RZP\Models\Merchant\MerchantApplications;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        ENTITY::TYPE            => 'required|max:255',
        ENTITY::APPLICATION_ID  => 'required|size:14',
    ];
}
