<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::FUND_ACCOUNT_VALIDATION;

    protected $expands = [
        Entity::FUND_ACCOUNT,
    ];
}
