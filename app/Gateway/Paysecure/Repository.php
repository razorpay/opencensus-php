<?php

namespace RZP\Gateway\Paysecure;

use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'paysecure';

    protected $appFetchParamRules = [
        Entity::PAYMENT_ID             => 'sometimes|string|size:14',
        Entity::GATEWAY_TRANSACTION_ID => 'sometimes',
        Entity::RRN                    => 'sometimes',
        Entity::APPRCODE               => 'sometimes',
    ];
}
