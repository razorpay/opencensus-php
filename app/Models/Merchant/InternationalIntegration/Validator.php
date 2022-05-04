<?php

namespace RZP\Models\Merchant\InternationalIntegration;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID            => 'required|string|size:14',
        Entity::INTEGRATION_ENTITY     => 'required|string',
        Entity::INTEGRATION_KEY        => 'required|string',
        Entity::NOTES                  => 'sometimes',
    ];

    protected static $editRules = [
        Entity::MERCHANT_ID            => 'required|string|size:14',
        Entity::INTEGRATION_ENTITY     => 'required|string',
        Entity::INTEGRATION_KEY        => 'required|string',
        Entity::NOTES                  => 'sometimes',
    ];

}
