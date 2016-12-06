<?php

namespace RZP\Gateway\Cybersource;

use RZP\Exception;
use RZP\Gateway\Cybersource;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'cybersource';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID   => 'sometimes|string|size:14',
        Entity::REFUND_ID    => 'sometimes|string|size:14',
        Entity::RECEIVED     => 'sometimes|boolean',
        Entity::REF          => 'sometimes|string',
        Entity::CAPTURE_REF  => 'sometimes|string'
    );
}
