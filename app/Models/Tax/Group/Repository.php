<?php

namespace RZP\Models\Tax\Group;

use RZP\Models\Base;
use RZP\Models\Item;

class Repository extends Base\Repository
{
    protected $entity = 'tax_group';

    /**
     * Ref: Tax\Repository
     *
     * @param Entity $entity
     *
     */
    public function deleteOrFail($entity)
    {
        $entity->items()->update([Item\Entity::TAX_GROUP_ID => null]);

        return parent::deleteOrFail($entity);
    }
}
