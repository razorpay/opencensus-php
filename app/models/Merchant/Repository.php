<?php

namespace Models\Merchant;

use Constants\Mode;
use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Merchant;
use Models\Merchant\Balance;
use Models\Pricing;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;
    use Base\RepositoryFetch;

    protected $entity = 'Merchant';

    protected $appFetchParamRules = array(
        Entity::ACTIVATED               => 'sometimes|boolean',
        Entity::HOLD_FUNDS              => 'sometimes|boolean',
        Entity::LIVE                    => 'sometimes|boolean',
        Entity::EMAIL                   => 'sometimes|string|max:255',
        Entity::CATEGORY                => 'sometimes|integer|digits:4',
        Entity::INTERNATIONAL           => 'sometimes|boolean',
        Entity::RECEIPT_EMAIL_ENABLED   => 'sometimes|boolean',
        Entity::METHODS                 => 'sometimes|string',
        Entity::PRICING_PLAN_ID         => 'sometimes|string',
    );

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

    public function getCountOfMerchantsActivatedBetween($from, $to)
    {

        $repo = $this->repo;

        return $repo::whereBetween(Entity::ACTIVATED_AT, [$from, $to])->count();

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
        $repo = $this->repo;

        return $repo::all(['name', 'email', 'transaction_report_email']);
    }

    public function fetchMerchantWhereTestBankIsNull()
    {
        $repo = new $this->repo;
        return $repo->setConnection(Mode::TEST)
                    ->has('bankAccount', '<', 1)
                    ->get();
    }
    public function fetchAllLiveMerchants()
    {
        $repo = $this->repo;

        return $repo::where(Entity::LIVE, '=', 1);
    }
}
