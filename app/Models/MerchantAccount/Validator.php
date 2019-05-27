<?php

namespace RZP\Models\MerchantAccount;

use RZP\Base;

class Validator extends Base\Validator
{
     protected static $createRules = [
        Entity::BANK            => 'required|string|in:Rbl',
        Entity::PINCODE         => 'required_if:bank,Rbl',
        Entity::MERCHANT_ID     => 'required|size:14'
    ];
}
