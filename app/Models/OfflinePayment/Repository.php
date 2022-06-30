<?php

namespace RZP\Models\OfflinePayment;

use RZP\Constants;
use RZP\Models\Base;
Use RZP\Models\OfflinePayment\Entity as Entity;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::OFFLINE_PAYMENT;

    public function fetchByChallanNumber(string $challanNumber) {
        return $this->newQuery()
                    ->where(Entity::CHALLAN_NUMBER, '=', $challanNumber)
                    ->first();
    }
}
