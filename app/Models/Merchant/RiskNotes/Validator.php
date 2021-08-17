<?php

namespace RZP\Models\Merchant\RiskNotes;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NOTE            => 'required|string',
        Entity::ADMIN_ID        => 'required|string|size:14',
        Entity::MERCHANT_ID     => 'required|string|size:14'
    ];
}
