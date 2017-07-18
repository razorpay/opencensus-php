<?php

namespace RZP\Models\Payment;

use RZP\Models\Base;

class EsRepository extends Base\EsRepository
{
    protected $fields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::NOTES,
        Entity::CREATED_AT,
    ];

    protected $esFetchParams = [
        Entity::NOTES,
    ];
}
