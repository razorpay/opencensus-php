<?php

namespace RZP\Models\Settlement\OndemandFundAccount;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'settlement.ondemand_fund_account';

    public function findByMerchantId($merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->first();
    }
}
