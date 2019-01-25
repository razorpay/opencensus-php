<?php

namespace RZP\Models\P2p\Base;

use RZP\Models\Base;
use RZP\Base\BuilderEx;
use RZP\Models\P2p\Base\Traits\ApplicationTrait;

class Repository extends Base\Repository
{
    use ApplicationTrait;

    protected function getEntityObject()
    {
        $className = str_replace('\Repository', '\Entity', static::class);

        return new $className;
    }

    public function newP2pEntity(): Entity
    {
        $entity = $this->getEntityObject();

        if ($entity->hasDevice())
        {
            if ($this->context()->isContextDevice() === false)
            {
                $this->context()->throwContextException('Device is required for action.');
            }

            $entity->associateDevice($this->context()->getDevice());
        }

        if ($entity->hasMerchant())
        {
            if ($this->context()->isContextMerchant() === false)
            {
                $this->context()->throwContextException('Merchant is required for action.');
            }

            $entity->associateMerchant($this->context()->getMerchant());
        }

        if ($entity->hasHandle())
        {
            $entity->associateHandle($this->context()->getHandle());
        }

        return $entity;
    }


    public function newP2pQuery(): BuilderEx
    {
        $query = parent::newQuery();

        if ($query->getModel()->hasDevice())
        {
            if ($this->context()->isContextDevice() === false)
            {
                $this->context()->throwContextException('Device is required for action.');
            }

            $query->device($this->context()->getDevice());
        }

        if ($query->getModel()->hasMerchant())
        {
            if ($this->context()->isContextMerchant() === false)
            {
                $this->context()->throwContextException('Merchant is required for action.');
            }

            $query->merchant($this->context()->getMerchant());
        }

        if ($query->getModel()->hasHandle())
        {
            $query->handle($this->context()->getHandle());
        }

        return $query;
    }
}
