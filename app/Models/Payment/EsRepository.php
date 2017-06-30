<?php

namespace RZP\Models\Payment;

use RZP\Models\Base;
use RZP\Constants\Table;

class EsRepository extends Base\EsRepository
{
    protected static $table = Table::PAYMENT;

    protected $fields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::NOTES,
        Entity::CREATED_AT,
    ];
}
