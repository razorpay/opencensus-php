<?php

namespace RZP\Models\Coupon;

use RZP\Models\Base;
use RZP\Models\Schedule;
use RZP\Models\Promotion;
use RZP\Constants\Entity as PublicEntity;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $publicEntityId = $input[Entity::ENTITY_ID];

        (PublicEntity::getEntityClass($input[Entity::ENTITY_TYPE]))::stripSignWithoutValidation($input[Entity::ENTITY_ID]);

        $coupon = (new Entity)->build($input);

        $entityType = $input[Entity::ENTITY_TYPE];

        $entity = $this->repo->$entityType->findByPublicId($publicEntityId);

        $coupon->source()->associate($entity);

        $coupon = $entity->coupons()->save($coupon);

        return $coupon;
    }
}
