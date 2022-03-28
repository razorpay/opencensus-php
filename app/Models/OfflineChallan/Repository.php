<?php

namespace RZP\Models\OfflineChallan;

use RZP\Constants;
use RZP\Models\Base;
Use RZP\Models\OfflineChallan\Entity as Entity;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::OFFLINE_CHALLAN;

    public function fetchByChallanNumber(string $challanNumber) {
            return $this->newQuery()
                        ->where(Entity::CHALLAN_NUMBER, '=', $challanNumber)
                        ->first();
        }
}
