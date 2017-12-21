<?php

namespace RZP\Models\Merchant\EmiPlans;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_emi_plans';

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
                    ->select(Entity::EMI_PLAN_ID)
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->pluck(Entity::EMI_PLAN_ID)
                    ->all();
    }
}
