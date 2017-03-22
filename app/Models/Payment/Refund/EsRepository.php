<?php

namespace RZP\Models\Payment\Refund;

use RZP\Constants\Table;
use RZP\Models\Base;

class EsRepository extends Base\Es\Repository
{
    protected static $table = Table::REFUND;

    protected $fields = [
        Entity::ID,
        Entity::NOTES,
    ];
}
