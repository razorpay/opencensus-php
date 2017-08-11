<?php

namespace RZP\Models\Merchant\Invoice;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'merchant_invoice';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID     => 'sometimes|alpha_num|size:14',
        Entity::INVOICE_NUMNER  => 'sometimes|string',
        Entity::GSTIN           => 'sometimes|string|size:15',
    ];
}