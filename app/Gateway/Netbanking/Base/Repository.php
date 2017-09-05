<?php

namespace RZP\Gateway\Netbanking\Base;

use RZP\Exception;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'netbanking';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID          => 'sometimes|string|size:14',
        Entity::CAPS_PAYMENT_ID     => 'sometimes|string|size:14',
        Entity::BANK_PAYMENT_ID     => 'sometimes|string|max:20',
        Entity::INT_PAYMENT_ID      => 'sometimes',
    );

    public function findByIntPaymentId($intPaymentId)
    {
        return $this->newQuery()
                    ->where(Entity::INT_PAYMENT_ID, '=', $intPaymentId)
                    ->firstOrFail();
    }
}
