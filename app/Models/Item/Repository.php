<?php

namespace RZP\Models\Item;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'item';

    // These are merchant allowed params to search on. These also act as default params.
    protected $entityFetchParamRules = [
        Entity::ACTIVE => 'sometimes|boolean',
    ];

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'sometimes|alpha_num'
    ];
}
