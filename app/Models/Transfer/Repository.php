<?php

namespace RZP\Models\Transfer;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'transfer';

    protected $appFetchParamRules = [
        Entity::TRANSACTION_ID      => 'sometimes|alpha_num|size:14',
        Entity::MERCHANT_ID         => 'sometimes|alpha_num|size:14',
        Entity::SOURCE_ID           => 'sometimes|alpha_num|min:14',
        Entity::TO_ID               => 'sometimes|alpha_num|min:14'
    ];
}
