<?php

namespace RZP\Models\Risk;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'risk';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::MERCHANT_ID   => 'sometimes|alpha_num|size:14',
        Entity::PAYMENT_ID    => 'sometimes|alpha_dash|max:18',
        Entity::FRAUD_TYPE    => 'sometimes|string|max:20',
        Entity::SOURCE        => 'sometimes|string|max:20',
        Entity::RISK_SCORE    => 'sometimes|integer',
        Entity::REASON        => 'sometimes|string|max:150',
    ];

    protected $signedIds = [
        Entity::PAYMENT_ID,
    ];
}
