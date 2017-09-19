<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Item as ItemModel;

class Item extends Base
{
    // ---------- Creators for different type ------------------------------
    // By default every item gets created as invoice type.

    public function createPlanType(array $attributes = [])
    {
        $attributes[ItemModel\Entity::TYPE] = ItemModel\Type::PLAN;

        return parent::create($attributes);
    }

    public function createAddonType(array $attributes = [])
    {
        $attributes[ItemModel\Entity::TYPE] = ItemModel\Type::ADDON;

        return parent::create($attributes);
    }
}
