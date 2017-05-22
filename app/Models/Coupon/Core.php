<?php

namespace RZP\Models\Coupon;

use RZP\Models\Base;
use RZP\Models\Schedule;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $coupon = (new Entity)->build($input);

        if ($input[Entity::ENTITY_TYPE] === 'promotion')
        {
        	$entity = $this->repo->promotion->findByPublicId($input[Entity::ENTITY_ID]);
        }

        $coupon->source()->associate($entity);

        $coupon = $entity->coupons()->save($coupon);

        return $coupon;
    }
}
