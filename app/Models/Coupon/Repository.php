<?php

namespace RZP\Models\Coupon;

use DB;

class Repository extends Base\Repository
{
    protected $entity = 'coupon';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID         => 'sometimes|alpha_num',
        Entity::PROMOTION_ID        => 'sometimes|alpha_num',
        Entity::STARTS_AT           => 'sometimes|int',
        Entity::ENDS_AT             => 'sometimes|int',
    ];


    public function fetchByPromotion(string $id)
    {
        return $this->newQuery()
                    ->where(Entity::PROMOTION_ID, '=', $id)
                    ->get();
    }
}
