<?php

namespace RZP\Gateway\AxisMigs\Mock;

use RZP\Exception;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'refund';

    public function findByMerchantTxnRef($merchantTxnRef)
    {
        return $this->newQuery()
                    ->where('id', '=', $merchantTxnRef)
                    ->firstOrFail();
    }
}
