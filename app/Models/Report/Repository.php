<?php

namespace RZP\Models\Report;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'report';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID     => 'sometimes|alpha_dash',
    ];

    protected $proxyFetchParamRules = [
        Entity::TYPE            => 'sometimes|string'
    ];
}
