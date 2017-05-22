<?php

namespace RZP\Models\Coupon;

use DB;
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


    public function fetchByPromotion(string $id)
    {
        $id  = PublicEntity::stripSignWithoutValidation($id);

        return $this->newQuery()
                    ->where(Entity::PROMOTION_ID, '=', $id)
                    ->get();
    }
}
