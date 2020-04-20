<?php

namespace RZP\Gateway\Worldline;

use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'worldline';

    public function findByReferenceNumberAndAction(string $refNo, string $action)
    {
        return $this->newQuery()
                    ->where(Entity::REF_NO, '=', $refNo)
                    ->where(Entity::ACTION, '=', $action)
                    ->first();
    }
}
