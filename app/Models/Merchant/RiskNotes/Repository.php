<?php

namespace RZP\Models\Merchant\RiskNotes;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::MERCHANT_RISK_NOTE;

    protected $expands = [
        Entity::ADMIN,
        Entity::DELETED_BY_ADMIN,
    ];
}
