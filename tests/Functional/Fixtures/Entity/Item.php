<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Item as ItemModel;

class Item extends Base
{
    /**
     * Creates item entity of type plan
     *
     * @param array $attributes
     *
     * @return \RZP\Models\Item\Entity
     */
    public function createPlanType(array $attributes = [])
    {
        $attributes[ItemModel\Entity::TYPE] = ItemModel\Type::PLAN;

        return parent::create($attributes);
    }
}
