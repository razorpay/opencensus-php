<?php

namespace RZP\Models\Adjustment;

use RZP\Models\Base;
use RZP\Exception;

class Repository extends Base\Repository
{
    protected $entity = 'adjustment';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::TRANSACTION_ID  => 'sometimes|alpha_dash',
        Entity::SETTLEMENT_ID   => 'sometimes|alpha_dash'
    );

    public function findAdjustmentByDescription($description, $merchantId)
    {
        return $this->newQuery()
            ->where(Entity::DESCRIPTION, '=', $description)
            ->merchantId($merchantId)
            ->exists();
    }
}
