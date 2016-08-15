<?php

namespace RZP\Models\Invoice;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    // These are merchant allowed params to search on. These also act as default params.
    protected $entityFetchParamRules = [
        Entity::STATUS              => 'sometimes|string',
        Entity::SMS_STATUS          => 'sometimes|string',
        Entity::EMAIL_STATUS        => 'sometimes|string',
        Entity::CUSTOMER_EMAIL      => 'sometimes|email',
        Entity::CUSTOMER_CONTACT    => 'sometimes|string',
        Entity::CUSTOMER_ID         => 'sometimes|alpha_num',
    ];

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::STATUS              => 'sometimes|string',
        Entity::MERCHANT_ID         => 'sometimes|alpha_num',
    ];
}