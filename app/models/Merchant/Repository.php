<?php

namespace Models\Merchant;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Merchant;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'Merchant';

    public function getBalanceLockForUpdate($id)
    {
        return Merchant\Balance::lockForUpdate()->findOrFail($id);
    }

    public function updateBalance($balance)
    {
        $balance->saveOrFail();
    }

    public function getEscrowBalanceLockForUpdate()
    {
        $apiId = '134510ae166900007a9677a9';

        return $this->getBalanceLockForUpdate($apiId);
    }

    public function getPricingPlanOrFailPublic($merchant)
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