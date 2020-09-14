<?php

namespace RZP\Models\Payment\PaymentMeta;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'payment_meta';

    public function findByActionAndReferenceID($action, $referenceId)
    {
        return $this->newQuery()
                    ->where(Entity::REFERENCE_ID, $referenceId)
                    ->where(Entity::ACTION      , $action)
                    ->first();
    }
}
