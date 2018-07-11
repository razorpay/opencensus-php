<?php

namespace RZP\Gateway\Atom;

use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'atom';

    protected $appFetchParamRules = [
        Entity::PAYMENT_ID         => 'sometimes|string|min:14|max:18',
        Entity::REFUND_ID          => 'sometimes|string|min:14|max:18',
        Entity::BANK_PAYMENT_ID    => 'sometimes|string',
        Entity::GATEWAY_PAYMENT_ID => 'sometimes|string',
        Entity::RECEIVED           => 'sometimes|boolean',
    ];

    protected $signedIds = [
        Entity::PAYMENT_ID,
        Entity::REFUND_ID,
    ];
}
