<?php

namespace RZP\Models\VirtualAccountProducts;

use RZP\Constants;
use RZP\Constants\Entity as E;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::VIRTUAL_ACCOUNT_PRODUCTS;

    public function saveOrFail($virtualAccountProducts, array $options = array())
    {
        $order = $this->stripOrderRelationIfApplicable($virtualAccountProducts);

        parent::saveOrFail($virtualAccountProducts, $options);

        $this->associateOrderIfApplicable($virtualAccountProducts, $order);
    }

    protected function stripOrderRelationIfApplicable($virtualAccountProducts)
    {
        $entity = $virtualAccountProducts->entity;

        if (($entity === null) or
            ($entity->getEntityName() !== E::ORDER))
        {
            return;
        }

        $virtualAccountProducts->entity()->dissociate();

        $virtualAccountProducts->setAttribute(Entity::ENTITY_ID, $entity->getId());

        $virtualAccountProducts->setAttribute(Entity::ENTITY_TYPE, E::ORDER);

        return $entity;
    }

    public function associateOrderIfApplicable($virtualAccountProducts, $order)
    {
        if ($order === null)
        {
            return;
        }

        $virtualAccountProducts->entity()->associate($order);
    }

}
