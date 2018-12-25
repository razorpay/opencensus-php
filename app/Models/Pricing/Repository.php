<?php

namespace RZP\Models\Pricing;

use App;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Pricing;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Constants\Product;
use RZP\Models\Admin\Action;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'pricing';

    const WITH_TRASHED = 'deleted';

    protected $appFetchParamRules = array(
        Entity::PLAN_ID         => 'sometimes|string',
        self::WITH_TRASHED      => 'sometimes|in:0,1',
    );


    protected function newQueryWitOrgIdParam()
    {
         $query = $this->newQuery();
         return $this->addQueryParamOrgId($query);
    }

    protected function addQueryParamOrgId($query)
    {
        $app = App::getFacadeRoot();

        $orgId = $app['basicauth']->getOrgId();

        $orgId = Org\Entity::verifyIdAndStripSign($orgId);

        $query = $query->where(Pricing\Entity::ORG_ID, '=', $orgId);

        return $query;
    }

    public function getPricingPlanById($id, $fail = false, $public = false)
    {
        $pricing = $this->newQueryWitOrgIdParam()
                        ->where(Pricing\Entity::PLAN_ID, '=', $id)
                        ->orderBy(Pricing\Entity::PLAN_ID, 'desc')
                        ->orderBy(Pricing\Entity::PAYMENT_METHOD, 'desc')
                        ->orderBy(Pricing\Entity::ID, 'desc')
                        ->get();

        if (($pricing->count() === 0) and
            ($fail))
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
        return $this->newQuery()
                    ->where(Pricing\Entity::PLAN_ID, '=', $id)
                    ->orderBy(Pricing\Entity::PLAN_ID, 'desc')
                    ->orderBy(Pricing\Entity::PAYMENT_METHOD, 'desc')
                    ->orderBy(Pricing\Entity::ID, 'desc')
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

    public function getZeroPricingPlanRuleForMethod($feature, $method, $merchant, $product = Product::PRIMARY)
    {
        $orgId = $merchant->org->getId();

        return $this->newQuery()
                    ->product($product)
                    ->planId(Pricing\Entity::ZERO_PRICING)
                    ->where(Pricing\Entity::FEATURE, '=', $feature)
                    ->where(Pricing\Entity::ORG_ID, '=', $orgId)
                    ->where(Pricing\Entity::PAYMENT_METHOD, '=', $method)
                    ->firstOrFail();
    }

    public function getBankingPricingRulesForMethod(string $feature, string $method, Merchant\Entity $merchant)
    {
        $orgId = $merchant->org->getId();

        return $this->newQuery()
                    ->product(Product::BANKING)
                    ->planId(Fee::DEFAULT_BANKING_PLAN_ID)
                    ->where(Pricing\Entity::FEATURE, '=', $feature)
                    ->where(Pricing\Entity::ORG_ID, '=', $orgId)
                    ->where(Pricing\Entity::PAYMENT_METHOD, '=', $method)
                    ->get();
    }

    public function getPricingPlansOrderedByPlanId()
    {
        return $this->newQueryWitOrgIdParam()
                    ->orderBy(Pricing\Entity::PLAN_ID, 'desc')
                    ->orderBy(Pricing\Entity::PAYMENT_METHOD, 'desc')
                    ->orderBy(Pricing\Entity::ID, 'desc')
                    ->get();
    }

    public function getMerchantPricingPlans()
    {
        // For merchant pricing plans, gateway will not be specified
        return $this->newQueryWitOrgIdParam()
                    ->whereNull(Pricing\Entity::GATEWAY)
                    ->orderBy(Pricing\Entity::PLAN_ID, 'desc')
                    ->orderBy(Pricing\Entity::PAYMENT_METHOD, 'desc')
                    ->orderBy(Pricing\Entity::ID, 'desc')
                    ->get();
    }

    public function getMerchantPricingPlansSummary()
    {
        return $this->newQueryWitOrgIdParam()
                    ->selectRaw(
                       Pricing\Entity::PLAN_ID . ','.
                       Pricing\Entity::PLAN_NAME . ','.
                       'COUNT(*) AS rules_count')
                    ->groupBy(Pricing\Entity::PLAN_ID, Pricing\Entity::PLAN_NAME)
                    ->orderBy(Pricing\Entity::PLAN_ID, 'desc')
                    ->get();
    }

    public function getGatewayPricingPlans()
    {
        return $this->newQueryWitOrgIdParam()
                    ->whereNotNull(Pricing\Entity::GATEWAY)
                    ->orderBy(Pricing\Entity::ID, 'desc')->get();
    }

    public function getPricingPlanByName($name)
    {
        return $this->newQueryWitOrgIdParam()
                    ->where(Pricing\Entity::PLAN_NAME, '=', $name)
                    ->orderBy(Pricing\Entity::PAYMENT_METHOD, 'desc')
                    ->orderBy(Pricing\Entity::ID, 'desc')
                    ->get();
    }

    public function getPricingPlanRule($planId, $ruleId)
    {
        return $this->newQueryWitOrgIdParam()
                     ->planId($planId)
                     ->where(Entity::ID, '=', $ruleId)
                     ->firstOrFailPublic();
    }

    public function deletePlanRule($planId, $ruleId)
    {
        $rule = $this->newQueryWitOrgIdParam()
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
        $rule = $this->newQueryWitOrgIdParam()
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
