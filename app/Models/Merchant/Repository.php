<?php

namespace RZP\Models\Merchant;

use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Balance;
use RZP\Models\Pricing;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'Merchant';

    protected $sharedMerchant = null;

    protected $appFetchParamRules = array(
        Entity::ACTIVATED               => 'sometimes|boolean',
        Entity::HOLD_FUNDS              => 'sometimes|boolean',
        Entity::LIVE                    => 'sometimes|boolean',
        Entity::EMAIL                   => 'sometimes|string|max:255',
        Entity::CATEGORY                => 'sometimes|string|max:4',
        Entity::INTERNATIONAL           => 'sometimes|boolean',
        Entity::RECEIPT_EMAIL_ENABLED   => 'sometimes|boolean',
        Entity::METHODS                 => 'sometimes|string',
        Entity::PRICING_PLAN_ID         => 'sometimes|string',
    );

    public function getSharedAccount()
    {
        if ($this->sharedMerchant === null)
        {
            $this->sharedMerchant = $this->newQuery()
                                         ->where(Entity::ID, "=", Account::SHARED_ACCOUNT)
                                         ->firstOrFail();
        }

        return $this->sharedMerchant;
    }

    public function getPricingPlanOrFailPublic($merchant)
    {
        $pricing = $merchant->getPricingPlanId();

        if ($pricing === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_NOT_DEFINED_FOR_MERCHANT);
        }

        return (new Pricing\Repository)->getPricingPlanById($pricing, true, true);
    }

    public function fetchMerchantsWithPositiveBalance()
    {
        return $this->newQuery()
                    ->whereHas('balance', function($q)
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
        // 00:00 Today
        $today = \Carbon\Carbon::today("Asia/Kolkata")->timestamp;

        $start = \Carbon\Carbon::today("Asia/Kolkata")->subWeeks(3);

        return $this->newQuery()->whereBetween(Entity::CREATED_AT, [$start, $today]);
    }

    public function fetchBySettlementScheduleId($settlementScheduleIds)
    {
        return $this->newQuery()
                    ->whereNotNull(Entity::SETTLEMENT_SCHEDULE_ID)
                    ->whereIn(Entity::SETTLEMENT_SCHEDULE_ID, $settlementScheduleIds)
                    ->get();
    }

    public function getCountOfMerchantsActivatedBetween($from, $to)
    {
        return $this->newQuery()
                    ->whereBetween(Entity::ACTIVATED_AT, [$from, $to])
                    ->count();
    }

    public function addQueryParamMethods($query, $params)
    {
        $query->join(
            Methods\Entity::getTableName(),
            function ($join) use ($params)
            {
                $merchantId = Merchant\Entity::getAttributeWithTableName(Merchant\Entity::ID);
                $methodsMerchantId = Methods\Entity::getAttributeWithTableName(Methods\Entity::MERCHANT_ID);

                $methods = json_decode($params[Entity::METHODS], true);

                $join->on($methodsMerchantId, '=', $merchantId);

                foreach ($methods as $method => $value)
                {
                    $queryValue = null;

                    if ($value === 'true')
                        $queryValue = '1';
                    else if ($value === 'false')
                        $queryValue = '0';

                    $join->where($method, '=', $queryValue);
                }
            });

        $query->select($query->getModel()->getTable().'.*');
    }

    /**
     * Returns all the emails and names for all Merchants
     * No limits
     * @return [type] [description]
     */
    public function fetchAllMerchantContacts()
    {
        return $this->newQuery()
                    ->all(['name', 'email', 'transaction_report_email']);
    }

    public function fetchMerchantWhereTestBankIsNull()
    {
        $repo = $this->repo;

        return $repo::setConnection(Mode::TEST)
                    ->has('bankAccount', '<', 1)
                    ->get();
    }

    public function fetchAllLiveMerchants()
    {
        return $this->newQuery()
                    ->where(Entity::LIVE, '=', 1);
    }

    public function getMerchantFromEntity($entity)
    {
        $merchantId = $entity->getMerchantId();

        $merchant = $this->findOrFail($merchantId);

        $entity->merchant()->associate($merchant);

        return $merchant;
    }
}
