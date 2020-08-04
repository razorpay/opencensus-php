<?php

namespace RZP\Models\Merchant\Balance\LowBalanceConfig;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::LOW_BALANCE_CONFIG;

    public function findByBalanceIdAndMerchantId(string $balanceId, string $merchantId)
    {
        return $this->newQuery()
             ->where(Entity::BALANCE_ID, '=', $balanceId)
             ->where(Entity::MERCHANT_ID, '=', $merchantId)
             ->get();
    }
}
