<?php

namespace RZP\Models\Coupon;

use RZP\Models\Base\PublicEntity;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'coupon';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID         => 'sometimes|alpha_num',
        Entity::ENTITY_ID           => 'sometimes|alpha_num',
        Entity::ENTITY_TYPE         => 'sometimes|string',
    ];

    public function fetchByCode($code)
    {
        return $this->newQuery()
                    ->where(Entity::CODE, '=', $code)
                    ->firstOrFail();
    }
}
