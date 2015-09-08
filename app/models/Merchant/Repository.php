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

    protected $appFetchParamRules = array(
        Entity::ACTIVATED       => 'sometimes|boolean',
        Entity::HOLD_FUNDS      => 'sometimes|boolean',
        Entity::LIVE            => 'sometimes|boolean',
    );

    public function getBalanceLockForUpdate($id)
    {
        return Merchant\Balance::lockForUpdate()->findOrFail($id);
    }

    public function getMerchantBalanceLockForUpdate($merchant)
    {
        return $this->getBalanceLockForUpdate($merchant->getKey());
    }

    public function getMerchantBalance($merchant)
    {
        return Merchant\Balance::findOrFailPublic($merchant->getId());
    }

    public function updateBalance($balance)
    {
        $balance->saveOrFail();
    }

    public function getEscrowBalanceLockForUpdate($channel)
    {
        $func = 'get'.ucfirst($channel).'BalanceLockForUpdate';

        return $this->$func();
    }

    public function getKotakBalanceLockForUpdate()
    {
        return $this->getBalanceLockForUpdate(Merchant\Account::NODAL_ACCOUNT);
    }

    public function getAtomBalanceLockForUpdate()
    {
        return $this->getBalanceLockForUpdate(Merchant\Account::ATOM_ACCOUNT);
    }

    public function getPricingPlanOrFailPublic($merchant)
    {
        $pricing = $merchant->getPricingPlanId();

        if ($pricing === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_NOT_DEFINED_FOR_MERCHANT);
        }

        return $pricing;
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

    public function fetchRecentMerchants()
    {
        $repo = $this->repo;

        // 00:00 Today
        $today = \Carbon\Carbon::today("Asia/Kolkata")->timestamp;

        $start = \Carbon\Carbon::today("Asia/Kolkata")->subWeeks(3);

        return $repo::whereBetween(Entity::CREATED_AT, [$start, $today]);
    }
}
