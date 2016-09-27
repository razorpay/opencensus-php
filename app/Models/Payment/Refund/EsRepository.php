<?php

namespace RZP\Models\Payment\Refund;

use RZP\Constants\Table;
use RZP\Models\Base;

class EsRepository extends Base\EsRepository
{
    protected static $table = Table::REFUND;
}
