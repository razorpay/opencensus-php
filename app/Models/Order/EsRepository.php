<?php

namespace RZP\Models\Order;

use RZP\Models\Base;
use RZP\Constants\Table;

class EsRepository extends Base\Es\Repository
{
    protected static $table = Table::ORDER;

    protected $fields = [
        Entity::ID,
        Entity::NOTES,
    ];
}
