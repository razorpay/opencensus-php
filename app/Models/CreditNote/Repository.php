<?php

namespace RZP\Models\CreditNote;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'creditnote';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID         => 'sometimes|alpha_num|max:14',
        Entity::SUBSCRIPTION_ID     => 'sometimes|alpha_num|max:14',
    ];

    protected $proxyFetchParamRules = [
        Entity::SUBSCRIPTION_ID     => 'sometimes|string|min:14|max:18',
    ];

    protected $signedIds = [
        Entity::SUBSCRIPTION_ID,
    ];
}
