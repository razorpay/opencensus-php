<?php

namespace RZP\Gateway\Aeps\Base;

use RZP\Gateway\Base\Repository as BaseRepository;

class Repository extends BaseRepository
{
    protected $entity = 'aeps';

    protected $appFetchParamRules = [
        Entity::PAYMENT_ID => 'sometimes|string|size:14',
        Entity::RRN        => 'sometimes|string'
    ];
}
