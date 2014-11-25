<?php

namespace Models\Merchant;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Merchant;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;
    use Base\RepositoryFetch;

    protected $entity = 'Merchant';

    public function getBalanceLockForUpdate($id)
    {
        return Merchant\Balance::lockForUpdate()->findOrFail($id);
    }

    public function getMerchantBalanceLockForUpdate($merchant)
    {
        return $this->getBalanceLockForUpdate($merchant->getKey());
    }

    public function updateBalance($balance)
    {
        $balance->saveOrFail();
    }

    public function getEscrowBalanceLockForUpdate()
    {
        $apiId = '1cXSLlUU8V9sXl';

        return $this->getBalanceLockForUpdate($apiId);
    }

    public function getPricingPlanOrFailPublic($merchant)
    {
        $pricing = $merchant->getPricingPlanId();

        if ($pricing === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_NOT_DEFINED_FOR_MERCHANT);
        }

        return $merchant->pricingPlan();
    }

    public function fetchMerchantsWithPositiveBalance()
    {
        $repo = $this->repo;

        return $repo::whereHas('balance', function($q)
        {
            $q->where('balance', '>', 0);
        })->get();
    }

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }
}