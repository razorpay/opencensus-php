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

    public function fetchByMerchant(string $merchantId)
    {
        return $this->newQuery()
                    ->select(Entity::EMI_PLAN_ID, Entity::MERCHANT_PAYBACK)
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->pluck(Entity::MERCHANT_PAYBACK, Entity::EMI_PLAN_ID)
                    ->all();
    }
}
