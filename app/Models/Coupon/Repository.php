<?php

namespace RZP\Models\Coupon;

use RZP\Models\Base\PublicEntity;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'coupon';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID         => 'sometimes|alpha_num',
        Entity::ENTITY_ID           => 'required|alpha_num',
        Entity::ENTITY_TYPE         => 'required|string',
    ];
}
