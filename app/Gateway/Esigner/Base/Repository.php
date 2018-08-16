<?php

namespace RZP\Gateway\Esigner\Base;

use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'esigner';

    public function findByMandateIdAndAction(string $mandateId, $action)
    {
        return $this->newQuery()
            ->where(Entity::MANDATE_ID, $mandateId)
            ->where(Entity::ACTION, $action)
            ->firstOrFail();
    }
}
