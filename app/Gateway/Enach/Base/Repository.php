<?php

namespace RZP\Gateway\Enach\Base;

use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'enach';

    protected $appFetchParamRules = [
        Entity::PAYMENT_ID          => 'sometimes|string|size:14',
        Entity::UMRN                => 'sometimes|string',
    ];
}
