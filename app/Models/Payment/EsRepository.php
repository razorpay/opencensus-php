<?php

namespace RZP\Models\Payment;

use RZP\Models\Base;
use RZP\Constants\Table;

class EsRepository extends Base\Es\Repository
{
    protected static $table = Table::PAYMENT;

    protected $fields = [
        Entity::ID,
        Entity::NOTES,
    ];
}
