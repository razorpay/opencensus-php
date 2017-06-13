<?php

namespace RZP\Models\Coupon;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'coupon';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID         => 'sometimes|alpha_num|max:14',
        Entity::ENTITY_ID           => 'sometimes|alpha_num|max:14',
        Entity::ENTITY_TYPE         => 'sometimes|string',
    ];

    public function fetchByCode(string $code)
    {
        return $this->newQuery()
                    ->where(Entity::CODE, '=', $code)
                    ->first();
    }
}
