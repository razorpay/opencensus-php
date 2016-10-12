<?php

namespace RZP\Models\Order;

use RZP\Models\Base;
use RZP\Constants\Table;

class EsRepository extends Base\EsRepository
{
    protected static $table = Table::ORDER;
}