<?php

namespace RZP\Models\Merchant\Tnc;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID            => 'required|string|size:14',
        Entity::DELIVERABLE_TYPE       => 'required|string|in:goods,services',
        Entity::SHIPPING_PERIOD        => 'sometimes|string',
        Entity::REFUND_REQUEST_PERIOD  => 'required|string',
        Entity::REFUND_PROCESS_PERIOD  => 'required|string',
        Entity::WARRANTY_PERIOD        => 'sometimes|string',
    ];

    protected static $editRules = [
        Entity::MERCHANT_ID            => 'sometimes|string|size:14',
        Entity::DELIVERABLE_TYPE       => 'required|string|in:goods,services',
        Entity::SHIPPING_PERIOD        => 'sometimes|string',
        Entity::REFUND_REQUEST_PERIOD  => 'required|string',
        Entity::REFUND_PROCESS_PERIOD  => 'required|string',
        Entity::WARRANTY_PERIOD        => 'sometimes|string',
    ];
}
