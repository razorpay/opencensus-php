<?php

namespace RZP\Models\Payout;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'payout';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::MERCHANT_ID        => 'sometimes|alpha_num',
        Entity::CUSTOMER_ID        => 'sometimes|alpha_num|size:14',
        Entity::METHOD             => 'sometimes|string',
    ];
}
