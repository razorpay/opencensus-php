<?php

namespace RZP\Models\P2p\Base;

use RZP\Models\Base;
use RZP\Models\P2p\Base\Traits\ApplicationTrait;

class Repository extends Base\Repository
{
    use ApplicationTrait;

    public function newEntity(): Entity
    {
        $entity = $this->getEntityObject();

        if ($entity->hasDevice())
        {
            if ($this->context()->isContextDevice() === false)
            {
                $this->context()->throwContextException('Device is required for action.');
            }

            $entity->setDevice($this->context()->getDevice());
        }

        if ($entity->hasMerchant())
        {
            if ($this->context()->isContextMerchant() === false)
            {
                $this->context()->throwContextException('Merchant is required for action.');
            }

            $entity->setMerchant($this->context()->getMerchant());
        }

        if ($entity->hasHandle())
        {
            $entity->handleRelation()->associate($this->context()->getHandle());
        }

        return $entity;
    }
}
