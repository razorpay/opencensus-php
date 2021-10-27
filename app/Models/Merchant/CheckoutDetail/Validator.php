<?php

namespace RZP\Models\Merchant\CheckoutDetail;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID           => 'required|string|size:14',
        Entity::STATUS_1CC            => 'required|string|nullable|in:live,waitlisted,deactivated,available,interested',
    ];

    protected static $editRules = [
        Entity::MERCHANT_ID           => 'required|string|size:14',
        Entity::STATUS_1CC            => 'required|string|nullable|in:live,waitlisted,deactivated,available,interested',
    ];
}
