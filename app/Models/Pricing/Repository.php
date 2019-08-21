<?php

namespace RZP\Models\Pricing;

use App;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Pricing;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Constants\Product;
use RZP\Models\Admin\Action;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Base\QueryCache\CacheQueries;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;
    use CacheQueries;

    protected $entity = 'pricing';

    const WITH_TRASHED = 'deleted';

    protected $appFetchParamRules = array(
        Entity::PLAN_ID         => 'sometimes|string',
        self::WITH_TRASHED      => 'sometimes|in:0,1',
    );


    protected function newQueryWithOrgIdParam()
    {
         $query = $this->newQuery();
         return $this->addQueryParamOrgId($query);
    }

    protected function addQueryParamOrgId($query)
    {
        $app = App::getFacadeRoot();

        $rzpOrgId = Org\Entity::getSignedId(Org\Entity::RAZORPAY_ORG_ID);

        $orgId = (empty($app['basicauth']->getOrgId()) === true) ? $rzpOrgId : $app['basicauth']->getOrgId();

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
        $pricing = $this->getPlan($id, Pricing\Type::PRICING, $fail, $public);

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
    public function getPlan(string $id, string $type = null, bool $fail = false, bool $public = false)
    {
        $query   = $this->newQueryWithOrgIdParam();

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

    // called in pricing fee calculation flow
    public function getPricingPlanByIdWithoutOrgId($id)
    {
        $cacheTags = Entity::getCacheTags($this->entity, $id);

        return $this->newQuery()
                    ->where(Pricing\Entity::PLAN_ID, '=', $id)
                    ->where(Pricing\Entity::TYPE, Pricing\Type::PRICING)
                    ->orderBy(Pricing\Entity::PLAN_ID, 'desc')
                    ->orderBy(Pricing\Entity::PAYMENT_METHOD, 'desc')
                    ->orderBy(Pricing\Entity::ID, 'desc')
                    ->remember($this->getCacheTtl())
                    ->cacheTags($cacheTags)
                    ->get();
    }

    public function getMerchantPricingPlan($merchant)
    {
        $pricingPlanId = $merchant->getPricingPlanId();

        return $this->getPricingPlanByIdOrFailPublic($pricingPlanId);
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
    public function getPlanByIdOrFailPublic($id)
    {
        return $this->getPlan($id, null, true, true);
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

    public function getBankingSharedAccountDefaultPricingRules(string $feature, Merchant\Entity $merchant)
    {
        $orgId = $merchant->getOrgId();

        return $this->newQuery()
                    ->product(Product::BANKING)
                    ->planId(Fee::DEFAULT_BANKING_PLAN_ID)
                    ->where(Pricing\Entity::FEATURE, '=', $feature)
                    ->where(Pricing\Entity::ACCOUNT_TYPE, AccountType::SHARED)
                    ->where(Pricing\Entity::ORG_ID, '=', $orgId)
                    ->where(Pricing\Entity::TYPE, Pricing\Type::PRICING)
                    ->get();
    }

    public function getBankingDirectAccountDefaultPricingRules(string $feature, Merchant\Entity $merchant)
    {
        $orgId = $merchant->getOrgId();

        return $this->newQuery()
                    ->product(Product::BANKING)
                    ->planId(Fee::DEFAULT_BANKING_PLAN_ID)
                    ->where(Pricing\Entity::FEATURE, '=', $feature)
                    ->where(Pricing\Entity::ACCOUNT_TYPE, AccountType::DIRECT)
                    ->where(Pricing\Entity::ORG_ID, '=', $orgId)
                    ->where(Pricing\Entity::TYPE, Pricing\Type::PRICING)
                    ->get();
    }

    public function getPlansOrderedByPlanId(array $input)
    {
        $query = $this->newQueryWithOrgIdParam();

        if (empty($input[Entity::TYPE]) === false)
        {
            $query->where(Pricing\Entity::TYPE, $input[Entity::TYPE]);
        }

        return $query->orderBy(Pricing\Entity::PLAN_ID, 'desc')
                     ->orderBy(Pricing\Entity::PAYMENT_METHOD, 'desc')
                     ->orderBy(Pricing\Entity::ID, 'desc')
                     ->get();
    }

    public function getMerchantPricingPlansSummary(array $input = [])
    {
        $query = $this->newQueryWithOrgIdParam();

        if (empty($input[Entity::TYPE]) === false)
        {
            $query->where(Pricing\Entity::TYPE, $input[Entity::TYPE]);
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

    public function getPlanRule($planId, $ruleId)
    {
        return $this->newQueryWithOrgIdParam()
                     ->planId($planId)
                     ->where(Entity::ID, '=', $ruleId)
                     ->firstOrFailPublic();
    }

    public function deletePlanRule($planId, $ruleId)
    {
        $rule = $this->newQueryWithOrgIdParam()
                     ->planId($planId)
                     ->where(Entity::ID, '=', $ruleId)
                     ->firstOrFailPublic();

        $rule->setAuditAction(Action::DELETE_PRICING_PLAN_RULE);

        $count = $rule->feesBreakup->count();

        if ($count === 0)
        {
            return $this->forceDelete($rule);
        }
        else
        {
            throw new Exception\BadRequestValidationFailureException(
                'Pricing rule cannot be deleted because it has been used more than once');
        }
    }

    public function deletePlanRuleForce($planId, $ruleId)
    {
        $rule = $this->newQueryWithOrgIdParam()
                     ->planId($planId)
                     ->where(Entity::ID, '=', $ruleId)
                     ->firstOrFailPublic();

        $rule->setAuditAction(Action::DELETE_PRICING_PLAN_RULE);

        //always soft delete the rule
        return $this->delete($rule);
    }

    protected function addQueryParamDeleted($query, $params)
    {
        if ($params[self::WITH_TRASHED] === '1')
        {
            $query->withTrashed();
        }
    }
}
