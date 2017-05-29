<?php

namespace RZP\Models\Tax;

use RZP\Models\Base;
use RZP\Models\Item;

class Repository extends Base\Repository
{
    protected $entity = 'tax';

    /**
     * @override
     *
     * We basically assign all items.tax_id with null where tax_id was equals to
     * one which is being soft deleted. There is constraint ON DELETE SET NULL
     * at MySQL level but because we're using SoftDelets trait that doesn't
     * get triggered.
     *
     * @param Entity $entity
     *
     */
    public function deleteOrFail($entity)
    {
        $entity->items()->update([Item\Entity::TAX_ID => null]);

        return parent::deleteOrFail($entity);
    }
}
