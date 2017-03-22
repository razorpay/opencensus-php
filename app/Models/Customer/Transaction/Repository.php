<?php

namespace RZP\Models\Customer\Transaction;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Customer;

class Repository extends Base\Repository
{
    protected $entity = 'customer_transaction';

    protected $entityFetchParamRules = [
        Entity::CUSTOMER_ID   => 'sometimes|string|size:14'
    ];

    protected $appFetchParamRules = [
        Entity::CUSTOMER_ID   => 'sometimes|string|size:14',
        Entity::MERCHANT_ID   => 'sometimes|string|size:14',
    ];
}
