<?php

namespace RZP\Models\Emi\MerchantSubvention;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'emi_merchant_subvention';

    public function fetchByMerchantAndEmiPlan(string $merchantId, string $emiPlanId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::EMI_PLAN_ID, '=', $emiPlanId)
                    ->first();
    }
}
