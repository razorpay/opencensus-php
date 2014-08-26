<?php

namespace Models\Merchant;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'Merchant';

    public function getBalanceLockForUpdate($id)
    {
        $repo = '\Models\Merchant\Balance';

        return $repo::lockForUpdate()->findOrFail($id);
    }

    public function getEscrowBalanceLockForUpdate()
    {
        $apiId = 'dd';

        return $this->getBalanceLockForUpdate($apiId);
    }

    public function getPricingPlan($merchant)
    {
        $pricing = $merchant->getPricingPlanId();

        if ($pricing === null)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_PRICING_NOT_DEFINED_FOR_MERCHANT);
        }

        return $merchant->pricingPlan();
    }
}