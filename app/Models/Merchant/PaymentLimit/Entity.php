<?php


namespace RZP\Models\Merchant\PaymentLimit;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    protected $entity = 'payment_limit';

    public function getMorphClass()
    {
        return $this->entity;
    }

}
