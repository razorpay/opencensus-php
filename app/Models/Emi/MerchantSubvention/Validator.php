<?php

namespace RZP\Models\Emi\MerchantSubvention;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::MERCHANT_PAYBACK        => 'sometimes|integer',
    );
}
