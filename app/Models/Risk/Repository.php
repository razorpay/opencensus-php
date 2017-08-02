<?php

namespace RZP\Models\Risk;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'risk';

    protected $merchantIdRequiredForMultipleFetch = false;

    // These are proxy allowed params to search on.
    protected $proxyFetchParamRules = [
        Entity::PAYMENT_ID    => 'sometimes|string|size:14',
        Entity::FRAUD_TYPE    => 'sometimes|string|max:20',
        Entity::SOURCE        => 'sometimes|boolean',
        Entity::COMMENTS      => 'sometimes|string|max:255',
        Entity::RISK_SCORE    => 'sometimes|integer',
        Entity::REASON        => 'sometimes|string|max:150',
    ];

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::MERCHANT_ID   => 'sometimes|string|size:14',
        Entity::PAYMENT_ID    => 'sometimes|string|size:14',
        Entity::FRAUD_TYPE    => 'sometimes|string|max:20',
        Entity::SOURCE        => 'sometimes|string|max:20',
        Entity::RISK_SCORE    => 'sometimes|integer',
        Entity::COMMENTS      => 'sometimes|string|max:255',
        Entity::REASON        => 'sometimes|string|max:150',
    ];
}
