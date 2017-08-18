<?php

namespace RZP\Models\Dispute;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'dispute';

    // These are merchant allowed params to search on. These also act as default params.
    protected $entityFetchParamRules = [
        Entity::STATUS             => 'sometimes|string',
        Entity::PAYMENT_ID         => 'sometimes|string|size:18',
        Entity::PHASE              => 'sometimes|string'
    ];

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::AMOUNT             => 'sometimes|integer',
        Entity::MERCHANT_ID        => 'sometimes|alpha_num',
    ];

    protected $signedIds = [
        Entity::PAYMENT_ID,
    ];
}
