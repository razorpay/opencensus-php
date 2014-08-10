<?php

namespace Models\Merchant;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'Merchant';

    public function findBalanceLockForUpdate($id)
    {
        $oldRepo = $this->repo;

        $this->repo = '\Models\Merchant\Balance';
        $repo = $this->repo;

        $balance = $repo::lockForUpdate()->findOrFail($id);

        $this->repo = $oldRepo;

        return $balance;
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