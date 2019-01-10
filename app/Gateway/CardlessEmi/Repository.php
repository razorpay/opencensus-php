<?php

namespace RZP\Gateway\CardlessEmi;

use RZP\Constants;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::CARDLESS_EMI;

    protected $appFetchParamRules = [
        Entity::PAYMENT_ID           => 'sometimes|string|min:14|max:18',
        Entity::REFUND_ID            => 'sometimes|string|min:14|max:18',
        Entity::GATEWAY_REFERENCE_ID => 'sometimes|string',
        Entity::RECEIVED             => 'sometimes|boolean',
    ];

    protected $signedIds = [
        Entity::PAYMENT_ID,
        Entity::REFUND_ID,
    ];
}
