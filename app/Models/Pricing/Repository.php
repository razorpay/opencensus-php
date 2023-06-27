<?php

namespace RZP\Models\Pricing;

use App;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\Pricing;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Constants\Product;
use RZP\Models\Admin\Action;
use RZP\Base\ConnectionType;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Base\QueryCache\CacheQueries;
use RZP\Trace\TraceCode;
use Database\Connection as Connection;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive
    {
        saveOrFail as saveOrFailTestAndLive;
    }
    use CacheQueries;

    protected $entity = 'pricing';

    const WITH_TRASHED = 'deleted';

    const WITH_BUY_PRICING      = 'with_buy_pricing';
    const WITHOUT_BUY_PRICING   = 'without_buy_pricing';
    const ONLY_BUY_PRICING      = 'only_buy_pricing';

    protected $buyPricingEnum = self::WITHOUT_BUY_PRICING;

    protected $appFetchParamRules = array(
        Entity::PLAN_ID         => 'sometimes|string',
        self::WITH_TRASHED      => 'sometimes|in:0,1',
    );

    protected $featureFilterParams = [
        Feature::PAYMENT,
        Feature::RECURRING,
    ];

    protected $productFilterParams = [
        Product::PRIMARY
    ];

    protected function newQuery()
    {
        return $this->addQueryParamBuyPricing(parent::newQuery());
    }

    protected function newQueryWithConnection($connection)
    {
        return $this->addQueryParamBuyPricing(parent::newQueryWithConnection($connection));
    }

    public function withBuyPricing()
    {
        return $this->setBuyPricingEnum(self::WITH_BUY_PRICING);
    }

    public function withoutBuyPricing()
    {
        return $this->setBuyPricingEnum(self::WITHOUT_BUY_PRICING);
    }

    public function onlyBuyPricing()
    {
        return $this->setBuyPricingEnum(self::ONLY_BUY_PRICING);
    }

    public function setBuyPricingEnum($value)
    {
        $this->buyPricingEnum = $value;

        return $this;
    }

    protected function newQueryWithOrgIdParam($orgId = null)
    {
        $query = $this->newQuery();
        return $this->addQueryParamOrgId($query, $orgId);
    }

    protected function newSlaveQueryWithOrgIdParam($orgId = null)
    {
        $query = $this->newQueryOnSlave();
        return $this->addQueryParamOrgId($query, $orgId);
    }

    protected function addQueryParamOrgId($query, $orgId = null)
    {
        $app = App::getFacadeRoot();

        $rzpOrgId = Org\Entity::getSignedId(Org\Entity::RAZORPAY_ORG_ID);

        if ($orgId === null)
        {
            $orgId = (empty($app['basicauth']->getOrgId()) === true) ? $rzpOrgId : $app['basicauth']->getOrgId();
        }

        $crossOrgId = $app['basicauth']->getCrossOrgId();

        if (empty($crossOrgId) === false)
        {
            $orgId = $crossOrgId;
        }
        elseif ($app['basicauth']->adminHasCrossOrgAccess() === true)
        {
            //
            // We don't need to add org filter to query if admin has accesss to other orgs also.
            //
            return $query;
        }

        $orgId = Org\Entity::verifyIdAndStripSign($orgId);

        $query = $query->where(Pricing\Entity::ORG_ID, '=', $orgId);

        return $query;
    }

    public function getPricingPlanById($id, $fail = false, $public = false)
    {
        $pricing = $this->getPlan($id, Pricing\Type::PRICING, $fail, $public, null, true);

        return $pricing;
    }

    public function getCommissionPlanById($id, $fail = false, $public = false)
    {
        $pricing = $this->getPlan($id, Pricing\Type::COMMISSION, $fail, $public);

        return $pricing;
    }

    /**
     * Get plan for a given id and type
     *
     * @param string      $id
     * @param string|null $type
     * @param bool        $fail
     * @param bool        $public
     *
     * @return mixed
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    public function getPlan(string $id, string $type = null, bool $fail = false, bool $public = false, string $orgId = null, bool $skipOrgCheck = false)
    {
        $query = $this->newQuery();

        if($skipOrgCheck !== true){
            $query   = $this->newQueryWithOrgIdParam($orgId);
        }

        $cacheTags = Entity::getCacheTags($this->entity, $id, $type);

        $query->where(Pricing\Entity::PLAN_ID, $id);

        if (empty($type) === false)
        {
            $query->where(Pricing\Entity::TYPE, $type);
        }

        $pricing = $query->orderBy(Pricing\Entity::PLAN_ID, 'desc')
                         ->orderBy(Pricing\Entity::PAYMENT_METHOD, 'desc')
                         ->orderBy(Pricing\Entity::ID, 'desc')
                         ->remember($this->getCacheTtl())
                         ->cacheTags($cacheTags)
                         ->get();

        if (($pricing->count() === 0) and ($fail))
        {
            if ($public)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_ID);
            }
            else
            {
                throw new Exception\LogicException(
                    'No pricing plan found for id: ' . $id);
            }
        }

        return $pricing;
    }

    public function getBuyPricingPlansByIds($ids)
    {
        sort($ids);

        $cacheTags = [];

        foreach ($ids as $id)
        {
            $tag = Entity::getCacheTags($this->entity, $id, Type::BUY_PRICING);

            array_push($cacheTags, $tag);
        }

        return $this->onlyBuyPricing()
                    ->newQueryOnSlave()
                    ->whereIn(Entity::PLAN_ID, $ids)
                    ->orderBy(Pricing\Entity::PLAN_ID, 'desc')
                    ->orderBy(Pricing\Entity::PAYMENT_METHOD, 'desc')
                    ->orderBy(Pricing\Entity::ID, 'desc')
                    ->remember($this->getCacheTtl())
                    ->cacheTags($cacheTags)
                    ->get();
    }

    public function getPricingPlanByIdAndOrgId($id, $orgId)
    {
        $pricing = $this->newQuery()
                        ->where(Pricing\Entity::PLAN_ID, '=', $id)
                        ->where(Pricing\Entity::ORG_ID, '=', $orgId)
                        ->where(Pricing\Entity::TYPE, Pricing\Type::PRICING)
                        ->orderBy(Pricing\Entity::PLAN_ID, 'desc')
                        ->orderBy(Pricing\Entity::PAYMENT_METHOD, 'desc')
                        ->orderBy(Pricing\Entity::ID, 'desc')
                        ->get();

        if ($pricing->count() === 0)
        {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        return $pricing;
    }

    public function getPricingPlanByIdWithProductAndFeatureFilter($id)
    {
        $pricing = $this->newQuery()
            ->where(Pricing\Entity::PLAN_ID, '=', $id)
            ->where(Pricing\Entity::TYPE, Pricing\Type::PRICING)
            ->whereIn(Pricing\Entity::PRODUCT, $this->productFilterParams)
            ->whereIn(Pricing\Entity::FEATURE, $this->featureFilterParams)
            ->orderBy(Pricing\Entity::PLAN_ID, 'desc')
            ->orderBy(Pricing\Entity::PAYMENT_METHOD, 'desc')
            ->orderBy(Pricing\Entity::ID, 'desc')
            ->get();

        if ($pricing->count() === 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        return $pricing;
    }

    // called in pricing fee calculation flow
    public function getPricingPlanByIdWithoutOrgId($id, $merchant = null)
    {
        return $this->repo->useSlave(function() use ($merchant, $id)
        {
            $cacheTags = Entity::getCacheTags($this->entity, $id);

            $query = $this->newQuery()
                ->where(Pricing\Entity::PLAN_ID, '=', $id)
                ->where(Pricing\Entity::TYPE, Pricing\Type::PRICING)
                ->orderBy(Pricing\Entity::PLAN_ID, 'desc')
                ->orderBy(Pricing\Entity::PAYMENT_METHOD, 'desc')
                ->orderBy(Pricing\Entity::ID, 'desc')
                ->remember($this->getCacheTtl())
                ->cacheTags($cacheTags);

            // see comment in config/pricing.php
            if (self::shouldDistributeQueryCacheLoad($merchant) === true)
            {
                $prefix = self::getQueryCachePrefixForDistributingLoad();

                $query = $query->prefix($prefix);
            }

            return $query->get();
        });
    }

    public function getPricingRulesByPlanIdFeatureAndInternationalWithoutOrgId(string $id,
                                                                               string $feature,
                                                                               int $international)
    {
        return $this->newQuery()
                    ->where(Pricing\Entity::PLAN_ID, '=', $id)
                    ->where(Pricing\Entity::TYPE, Pricing\Type::PRICING)
                    ->where(Pricing\Entity::FEATURE, '=', $feature)
                    ->where(Pricing\Entity::INTERNATIONAL, '=', $international)
                    ->get();
    }

    public function getPricingRulesByPlanIdProductAndFeatureWithoutOrgId(string $id,
                                                                         string $product,
                                                                         string $feature)
    {
        return $this->newQuery()
                    ->where(Pricing\Entity::PLAN_ID, '=', $id)
                    ->where(Pricing\Entity::TYPE, Pricing\Type::PRICING)
                    ->where(Pricing\Entity::PRODUCT, $product)
                    ->where(Pricing\Entity::FEATURE, '=', $feature)
                    ->get();
    }

    public function getMerchantPricingPlan($merchant)
    {
        $pricingPlanId = $merchant->getPricingPlanId();

        return $this->getPricingPlanByIdOrFailPublic($pricingPlanId);
    }

    public function getPricingRuleIdsByMerchant($merchant)
    {
        $pricingPlanId = $merchant->getPricingPlanId();

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                    ->where(Pricing\Entity::PLAN_ID, '=', $pricingPlanId)
                    ->pluck('id')
                    ->toArray();
    }

    public function fetchEligiblePlanIdsWithMissingCorporateRule(int $limit)
    {
        $slaveConnection = $this->getSlaveConnection();

        $query = (new Merchant\Repository)->newQueryWithConnection($slaveConnection);

        $pricingTable = $this->getTableName();

        $merchantsTable = (new Merchant\Repository)->getTableName();

        $merchantId = (new Merchant\Repository)->dbColumn('id');

        $merchantPricingPlanId = (new Merchant\Repository)->dbColumn('pricing_plan_id');

        $pricingPlanId = $this->dbColumn('plan_id');

        // SELECT COUNT(DISTINCT ms.pricing_plan_id) AS no_plans
        //FROM realtime_hudi_api.merchants as ms
        //WHERE ms.activated = 1
        //AND ms.id NOT in
        //(	select DISTINCT m.id
        //	from realtime_hudi_api.merchants as m
        //	INNER join realtime_hudi_api.pricing as p
        //	on m.pricing_plan_id = p.plan_id
        //	where p.payment_method = 'card'
        //	and p.payment_method_subtype = 'business'
        //	and p.deleted_at is null
        //)

        $query2 = $query->select($merchantPricingPlanId)->from($merchantsTable)
            ->where($merchantsTable . '.activated', '=', 1)->distinct()->whereNotIn(
                $merchantId,
                function($query3)
                use ($merchantId,
                    $pricingTable, $merchantPricingPlanId, $pricingPlanId, $merchantsTable)
                {
                    $query3->select($merchantId)->from($merchantsTable)->distinct()
                        ->join($pricingTable, $pricingPlanId, '=', $merchantPricingPlanId)
                        ->where($pricingTable . '.payment_method', '=', "card")
                        ->where($pricingTable . '.payment_method_subtype', '=', "business")
                        ->where($pricingTable . '.feature', '=', "payment")
                        ->where($pricingTable . '.type', '=', "pricing")
                        ->whereNull($pricingTable . '.deleted_at');
                })
            ->whereNotNull($merchantPricingPlanId)
            ->limit($limit)->pluck($merchantPricingPlanId);

       // sd($query->toSql());

        return $query2->toArray();
    }

    public function getPricingPlanByIdOrFailPublic($id)
    {
        return $this->getPricingPlanById($id, true, true);
    }

    /**
     * Fetches both types of pricing plans
     *
     * @param $id
     *
     * @return mixed
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    public function getPlanByIdOrFailPublic($id, $orgId = null, $skipOrgCheck = false)
    {
        return $this->getPlan($id, null, true, true, $orgId, $skipOrgCheck);
    }

    public function getZeroPricingPlanRuleForMethod($feature, $method, $merchant, $product = Product::PRIMARY)
    {
        $orgId = $merchant->org->getId();

        return $this->newQuery()
                    ->product($product)
                    ->planId(Pricing\Entity::ZERO_PRICING)
                    ->where(Pricing\Entity::FEATURE, '=', $feature)
                    ->where(Pricing\Entity::ORG_ID, '=', $orgId)
                    ->where(Pricing\Entity::PAYMENT_METHOD, '=', $method)
                    ->where(Pricing\Entity::TYPE, Pricing\Type::PRICING)
                    ->firstOrFail();
    }

    public function getInstantRefundsDefaultPricingPlanForMethod(
        $feature,
        $method,
        $product = Product::PRIMARY,
        $planId = Fee::DEFAULT_INSTANT_REFUNDS_PLAN_ID)
    {
        return $this->newQuery()
                    ->product($product)
                    ->planId($planId)
                    ->where(Pricing\Entity::FEATURE, '=', $feature)
                    ->where(function ($query) use ($method)
                    {
                        $query->where(Pricing\Entity::PAYMENT_METHOD, '=', $method)
                              ->orWhereNull(Pricing\Entity::PAYMENT_METHOD);
                    })
                    ->where(Pricing\Entity::TYPE, Pricing\Type::PRICING)
                    ->get();
    }

    /**
     * @param string $feature
     * @param Merchant\Entity $merchant
     * @return mixed
     *
     * only non app pricing rules from the default plan are fetched
     *
     */
    public function getBankingSharedAccountNonFreePayouDefaultPricingRules(string $feature, Merchant\Entity $merchant)
    {
        $orgId = $merchant->getOrgId();

        return $this->newQuery()
                    ->product(Product::BANKING)
                    ->planId(Fee::DEFAULT_BANKING_PLAN_ID)
                    ->where(Pricing\Entity::FEATURE, '=', $feature)
                    ->where(Pricing\Entity::ACCOUNT_TYPE, AccountType::SHARED)
                    ->where(Pricing\Entity::ORG_ID, '=', $orgId)
                    ->where(Pricing\Entity::TYPE, Pricing\Type::PRICING)
                    ->whereNull(Pricing\Entity::APP_NAME)
                    ->whereNull(Pricing\Entity::PAYOUTS_FILTER)
                    ->get();
    }

    /**
     * @param string $feature
     * @param Merchant\Entity $merchant
     * @param array $channels
     * @return mixed
     *
     * only non app pricing rules from the default plan are fetched
     *
     */
    public function getBankingDirectAccountNonFreePayoutDefaultPricingRules(string $feature, Merchant\Entity $merchant, array $channels)
    {
        $orgId = $merchant->getOrgId();

        return $this->newQuery()
                    ->product(Product::BANKING)
                    ->planId(Fee::DEFAULT_BANKING_PLAN_ID)
                    ->where(Pricing\Entity::FEATURE, '=', $feature)
                    ->where(Pricing\Entity::ACCOUNT_TYPE, AccountType::DIRECT)
                    ->where(Pricing\Entity::ORG_ID, '=', $orgId)
                    ->where(Pricing\Entity::TYPE, Pricing\Type::PRICING)
                    ->whereNull(Pricing\Entity::APP_NAME)
                    ->whereNull(Pricing\Entity::PAYOUTS_FILTER)
                    ->whereIn(Pricing\Entity::CHANNEL, $channels)
                    ->get();
    }

    /**
     * @param string $feature
     * @param Merchant\Entity $merchant
     * @return mixed
     *
     * only non app pricing rules from the default plan are fetched
     *
     */
    public function getBankingSharedAccountFreePayoutDefaultPricingRules(string $feature, Merchant\Entity $merchant)
    {
        $orgId = $merchant->getOrgId();

        return $this->newQuery()
                    ->product(Product::BANKING)
                    ->planId(Fee::DEFAULT_BANKING_PLAN_ID)
                    ->where(Pricing\Entity::FEATURE, '=', $feature)
                    ->where(Pricing\Entity::ACCOUNT_TYPE, AccountType::SHARED)
                    ->where(Pricing\Entity::ORG_ID, '=', $orgId)
                    ->where(Pricing\Entity::TYPE, Pricing\Type::PRICING)
                    ->whereNull(Pricing\Entity::APP_NAME)
                    ->where(Pricing\Entity::PAYOUTS_FILTER, '=', Payout\Entity::FREE_PAYOUT)
                    ->get();
    }

    /**
     * @param string $feature
     * @param Merchant\Entity $merchant
     * @return mixed
     *
     * only non app pricing rules from the default plan are fetched
     *
     */
    public function getBankingDirectAccountFreePayoutDefaultPricingRules(string $feature, Merchant\Entity $merchant)
    {
        $orgId = $merchant->getOrgId();

        return $this->newQuery()
                    ->product(Product::BANKING)
                    ->planId(Fee::DEFAULT_BANKING_PLAN_ID)
                    ->where(Pricing\Entity::FEATURE, '=', $feature)
                    ->where(Pricing\Entity::ACCOUNT_TYPE, AccountType::DIRECT)
                    ->where(Pricing\Entity::ORG_ID, '=', $orgId)
                    ->where(Pricing\Entity::TYPE, Pricing\Type::PRICING)
                    ->whereNull(Pricing\Entity::APP_NAME)
                    ->where(Pricing\Entity::PAYOUTS_FILTER, '=', Payout\Entity::FREE_PAYOUT)
                    ->get();
    }

    /**
     * @param string $feature
     * @param Merchant\Entity $merchant
     * @return mixed
     *
     * All the app pricing rules from the default plan are returned
     *
     */
    public function getAppPayoutPricingRules(string $feature, Merchant\Entity $merchant)
    {
        $orgId = $merchant->getOrgId();

        return $this->newQuery()
            ->product(Product::BANKING)
            ->planId(Fee::DEFAULT_BANKING_PLAN_ID)
            ->where(Pricing\Entity::FEATURE, '=', $feature)
            ->whereNotNull(Pricing\Entity::APP_NAME)
            ->where(Pricing\Entity::ORG_ID, '=', $orgId)
            ->where(Pricing\Entity::TYPE, Pricing\Type::PRICING)
            ->get();
    }

    public function getPlansOrderedByPlanId(array $input)
    {
        $query = $this->newSlaveQueryWithOrgIdParam();

        if (empty($input[Entity::TYPE]) === false)
        {
            $query->where(Pricing\Entity::TYPE, $input[Entity::TYPE]);
        }

        return $query->orderBy(Pricing\Entity::PLAN_ID, 'desc')
                     ->orderBy(Pricing\Entity::PAYMENT_METHOD, 'desc')
                     ->orderBy(Pricing\Entity::ID, 'desc')
                     ->get();
    }

    public function getPricingPlansSummary(array $input = [])
    {
        $query = $this->newQueryWithOrgIdParam();

        foreach ([Entity::TYPE, Entity::PLAN_ID, Entity::PLAN_NAME] as $attribute)
        {
            if (empty($input[$attribute]) === false)
            {
                $query->where($attribute, $input[$attribute]);
            }
        }

        return $query->selectRaw(
                       Pricing\Entity::PLAN_ID . ','.
                       Pricing\Entity::PLAN_NAME . ','.
                       Pricing\Entity::ORG_ID . ','.
                       Pricing\Entity::TYPE . ','.
                       'COUNT(*) AS rules_count')
                     ->groupBy(
                         Pricing\Entity::PLAN_ID,
                         Pricing\Entity::PLAN_NAME,
                         Pricing\Entity::ORG_ID,
                         Pricing\Entity::TYPE)
                     ->orderBy(Pricing\Entity::PLAN_ID, 'desc')
                     ->limit($input[Fetch::COUNT])
                     ->offset($input[Fetch::SKIP])
                     ->get();
    }

    public function getGatewayPricingPlans()
    {
        return $this->newQueryWithOrgIdParam()
                    ->where(Pricing\Entity::TYPE, Pricing\Type::PRICING)
                    ->whereNotNull(Pricing\Entity::GATEWAY)
                    ->orderBy(Pricing\Entity::ID, 'desc')->get();
    }

    public function getPlanByName($name)
    {
        return $this->newQueryWithOrgIdParam()
                    ->where(Pricing\Entity::PLAN_NAME, '=', $name)
                    ->orderBy(Pricing\Entity::PAYMENT_METHOD, 'desc')
                    ->orderBy(Pricing\Entity::ID, 'desc')
                    ->get();
    }

    public function getPlanRule($planId, $ruleId, $orgId = null)
    {
        return $this->newQueryWithOrgIdParam($orgId)
                     ->planId($planId)
                     ->where(Entity::ID, '=', $ruleId)
                     ->firstOrFailPublic();
    }

    public function deletePlanRuleForce($planId, $ruleId, $orgId = null)
    {
        $rule = $this->newQueryWithOrgIdParam($orgId)
                     ->planId($planId)
                     ->where(Entity::ID, '=', $ruleId)
                     ->firstOrFailPublic();

        $rule->setAuditAction(Action::DELETE_PRICING_PLAN_RULE);

        //always soft delete the rule
        return $this->delete($rule);
    }

    public function deleteBuyPlanGroupedRuleForce($planId, $input, $orgId = null)
    {
        $query = $this->newQueryWithOrgIdParam($orgId)
                      ->planId($planId);

        foreach ($input as $key => $value)
        {
            $query->where($key, '=', $value);
        }

        return $query->delete();
    }

    protected function addQueryParamDeleted($query, $params)
    {
        if ($params[self::WITH_TRASHED] === '1')
        {
            $query->withTrashed();
        }
    }

    protected function addQueryParamBuyPricing($query)
    {
        switch ($this->buyPricingEnum)
        {
            case self::WITH_BUY_PRICING :
                return $query;
            case self::ONLY_BUY_PRICING :
                return $query->where(Entity::TYPE, '=', Type::BUY_PRICING);
            default :
                return $query->where(Entity::TYPE, '!=', Type::BUY_PRICING);
        }
    }

    public function getPricingRuleByMultipleParams(
        $planId,
        $product,
        $feature,
        $method,
        $methodType,
        $methodSubtype,
        $network,
        $international,
        $amountRangeActive = 0,
        $orgId = null,
        $appName = null,
        $receiverType = null,
        $procurer = null,
    )
    {
        $rule = $this->newQueryWithOrgIdParam($orgId)
                     ->where(Entity::PLAN_ID, '=',$planId)
                     ->where(Entity::PRODUCT, '=', $product)
                     ->where(Entity::FEATURE, '=', $feature)
                     ->where(Entity::PAYMENT_METHOD, '=', $method)
                     ->where(Entity::PAYMENT_METHOD_TYPE, '=', $methodType)
                     ->where(Entity::PAYMENT_METHOD_SUBTYPE, '=', $methodSubtype)
                     ->where(Entity::PAYMENT_NETWORK, '=', $network)
                     ->where(Entity::INTERNATIONAL, '=', $international)
                     ->where(Entity::AMOUNT_RANGE_ACTIVE, '=', $amountRangeActive)
                     ->where(Entity::APP_NAME,'=',$appName);

        //Added for backward compatibility. If receiver_type is not empty only then filter
        if (!empty($receiverType)) {
            $rule = $rule->where(Entity::RECEIVER_TYPE,'=',$receiverType);
        }
        if (!empty($procurer)) {
            $rule = $rule->where(Entity::PROCURER,'=',$procurer);
        }

        return $rule->first();
    }

    public function getPricingRulesByPlanIdProductFeaturePaymentMethodOrgId($planId, $product, $feature, $method, $orgId = null)
    {
        $rule = $this->newQueryWithOrgIdParam($orgId)
                     ->where(Entity::PLAN_ID, '=',$planId)
                     ->where(Entity::PRODUCT, '=', $product)
                     ->where(Entity::FEATURE, '=', $feature)
                     ->where(Entity::PAYMENT_METHOD, '=', $method)
                     ->first();

        return $rule;
    }

    // In case of Current Account Payouts, fees is deducted at a later stage. There is a chance that a Pricing Rule
    // might have been deleted sometime between payout creation and transaction creation (which happens much later).
    // We use withTrashed to get pricingRules if they have been soft deleted so that feesBreakup remains consistent.
    public function getPricingFromPricingId($pricingRuleId, $withTrashed = false)
    {
        $idColumn = $this->dbColumn(Entity::ID);

        $query = $this->newQuery()
                      ->where($idColumn, $pricingRuleId);

        if ($withTrashed === true)
        {
            $query = $query->withTrashed();
        }

         return $query->first();
    }

    public static function shouldDistributeQueryCacheLoad($merchant) : bool
    {
        if ($merchant === null)
        {
            return false;
        }

        $app = App::getFacadeRoot();

        $config = $app['config']->get('pricing.query_cache_distribution');

        return in_array($merchant->getId(), $config['merchant_ids']) === true;
    }

    public static function getQueryCachePrefixForDistributingLoad(): string
    {
        $app = App::getFacadeRoot();

        $config = $app['config']->get('pricing.query_cache_distribution');

        $prefix = rand(1, $config['factor']);

        return strval($prefix);
    }

    public function saveOrFail($entity, array $options = array())
    {
        $entity = $this->transaction(function () use (& $entity, $options) {

            $this->saveOrFailTestAndLive($entity, $options);

            if ($entity->getType() === Type::BUY_PRICING)
            {
                $plan = $this->getPlan($entity->getPlanId(), Type::BUY_PRICING)->groupBy(Entity::PLAN_ID);

                $rules = ['rules' => $plan->toArray()];

                $this->app->smartRouting->syncBuyPricingRules($rules);
            }
        });

        return $entity;
    }
}
