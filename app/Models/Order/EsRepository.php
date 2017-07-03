<?php

namespace RZP\Models\Order;

use RZP\Models\Base;

class EsRepository extends Base\EsRepository
{
    protected $fields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::NOTES,
        Entity::CREATED_AT,
    ];
}
