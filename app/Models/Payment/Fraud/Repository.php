<?php

namespace RZP\Models\Payment\Fraud;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::PAYMENT_FRAUD;

    public function update($paymentId, $reportedBy, $params)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Entity::REPORTED_BY, '=', $reportedBy)
                    ->update($params);
    }
}
