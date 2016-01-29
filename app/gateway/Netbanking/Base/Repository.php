<?php

namespace Gateway\Netbanking\Base;

use EE\Exception;
use Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'netbanking';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID          => 'sometimes|string|size:14',
        Entity::CAPS_PAYMENT_ID     => 'sometimes|string|size:14',
        Entity::BANK_PAYMENT_ID     => 'sometimes|string|max:20',
        Entity::INT_PAYMENT_ID		=> 'sometimes|',
    );