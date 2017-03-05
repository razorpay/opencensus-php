<?php

namespace RZP\Models\Reversal;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'reversal';

    protected $appFetchParamRules = [
        Entity::TRANSACTION_ID      => 'sometimes|alpha_num|size:14',
        Entity::MERCHANT_ID         => 'sometimes|alpha_num|size:14',
        Entity::TRANSFER_ID         => 'sometimes|alpha_num|min:14',
    ];
}
