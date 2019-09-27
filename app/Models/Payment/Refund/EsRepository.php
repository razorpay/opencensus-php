<?php

namespace RZP\Models\Payment\Refund;

use RZP\Models\Base;
use RZP\Constants\Es;

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
}
