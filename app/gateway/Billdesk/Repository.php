<?php

namespace Gateway\Billdesk;

use EE\Exception;
use Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'Billdesk';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID              => 'sometimes|string|min:14|max:18',
        'TxnReferenceNo'                => 'sometimes|');
}