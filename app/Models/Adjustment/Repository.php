<?php

namespace RZP\Models\Adjustment;

use RZP\Models\Base;
use RZP\Exception;

class Repository extends Base\Repository
{
    protected $entity = 'adjustment';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::TRANSACTION_ID  => 'sometimes|alpha_num',
        Entity::SETTLEMENT_ID   => 'sometimes|alpha_num'
    );
}
