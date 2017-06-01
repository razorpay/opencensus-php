<?php

namespace RZP\Models\Coupon;

use RZP\Models\Base;
use RZP\Models\Schedule;
use RZP\Models\Base\PublicEntity;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $input['entity_id'] = PublicEntity::stripSignWithoutValidation($id);

        $coupon = (new Entity)->build($input);

        $entity = $this->repo->$input[Entity::ENTITY_TYPE]->findById($input[Entity::ENTITY_ID]);

        $coupon->source()->associate($entity);

        $coupon = $entity->coupons()->save($coupon);

        return $coupon;
    }
}
