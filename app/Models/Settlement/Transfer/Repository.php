<?php

namespace RZP\Models\Settlement\Transfer;

use RZP\Models\Base;
use RZP\Constants\Entity as EntityConstant;

class Repository extends Base\Repository
{
    protected $entity = EntityConstant::SETTLEMENT_TRANSFER;

    public function fetchBySettlementId($settlementId)
    {
        return $this->newQuery()
            ->where(Entity::SETTLEMENT_ID, '=', $settlementId)
            ->get();
    }
}
