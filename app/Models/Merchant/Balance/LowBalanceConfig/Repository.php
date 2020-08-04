<?php

namespace RZP\Models\Merchant\Balance\LowBalanceConfig;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::LOW_BALANCE_CONFIG;
}
