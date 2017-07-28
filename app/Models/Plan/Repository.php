<?php

namespace RZP\Models\Plan;

use RZP\Exception;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'plan';

    protected $proxyFetchParamRules = [
        Entity::PERIOD      => 'filled|string|max:16|custom',
        Entity::INTERVAL    => 'filled|integer|min:1|max:4000',
    ];

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID     => 'filled|string|size:14',
        Entity::ITEM_ID         => 'filled|string|min:14|max:19',
    ];

    protected $signedIds = [
        Entity::MERCHANT_ID,
        Entity::ITEM_ID,
    ];

    protected function validatePeriod($attribute, $value)
    {
        Cycle::validatePeriod($value);
    }
}
