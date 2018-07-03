<?php

namespace RZP\Models\VirtualAccount;

use RZP\Models\Base;

class EsRepository extends Base\EsRepository
{
    protected $indexedFields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::NOTES,
        Entity::CREATED_AT,
    ];

    protected $esFetchParams = [
        Entity::NOTES,
    ];

    protected $commonFetchParams = [
        Entity::MERCHANT_ID,
    ];
}
