<?php

namespace RZP\Models\Payment\Refund;

use RZP\Constants\Table;
use RZP\Models\Base;

class EsRepository extends Base\EsRepository
{
    protected static $table = Table::REFUND;

    protected $fields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::NOTES,
        Entity::CREATED_AT,
    ];
}
