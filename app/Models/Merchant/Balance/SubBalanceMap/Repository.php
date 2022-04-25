<?php

namespace RZP\Models\Merchant\Balance\SubBalanceMap;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::SUB_BALANCE_MAP;

    public function findByParentBalanceId(string $parentBalanceId)
    {
        return $this->newQueryWithConnection($this->getWhatsappSlaveConnection())
                    ->where(Entity::PARENT_BALANCE_ID, '=', $parentBalanceId)
                    ->get();
    }

}
