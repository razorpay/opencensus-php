<?php

namespace RZP\Models\Pricing;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'Pricing';

    protected $appFetchParamRules = array(
        Entity::PLAN_ID         => 'sometimes|string',
    );

    public function getPricingPlanById($id, $fail = false, $public = false)
    {
        $pricing = $this->newQuery()
                        ->where(Pricing\Entity::PLAN_ID, '=', $id)
                        ->orderBy(Pricing\Entity::PLAN_ID, 'desc')
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

    public function getMerchantPricingPlan($merchant)
    {
        $pricingPlanId = $merchant->getPricingPlanId();

        return $this->getPricingPlanByIdOrFailPublic($pricingPlanId);
    }

    public function getPricingPlanByIdOrFailPublic($id)
    {
        return $this->getPricingPlanById($id, true, true);
    }

    public function getPricingRulesForCard($id)
    {
        // cannot use laravel's whereIn here because it doesn't give correct result with 'null'
        return $this->newQuery()
                    ->planId($id)
                    ->where(Pricing\Entity::PAYMENT_METHOD, '=', Payment\Method::CARD)
                    ->orderBy(Pricing\Entity::ID, 'desc')
                    ->get();
    }

    public function getPricingRulesForMethod($pricingPlanId, $method)
    {
        return $this->newQuery()
                    ->planId($pricingPlanId)
                    ->where(Pricing\Entity::PAYMENT_METHOD, '=', $method)
                    ->get();
    }

    public function getZeroPricingPlanRuleForMethod($method)
    {
        return $this->newQuery()
                    ->planId(Pricing\Entity::ZERO_PRICING)
                    ->where(Pricing\Entity::PAYMENT_METHOD, '=', $method)
                    ->firstOrFail();
    }

    public function getPricingPlansOrderedByPlanId()
    {
        return $this->newQuery()
                    ->orderBy(Pricing\Entity::PLAN_ID, 'desc')
                    ->orderBy(Pricing\Entity::ID, 'desc')
                    ->get();
    }

    public function getMerchantPricingPlans()
    {
        // For merchant pricing plans, gateway will not be specified
        return $this->newQuery()
                    ->whereNull(Pricing\Entity::GATEWAY)
                    ->orderBy(Pricing\Entity::PLAN_ID, 'desc')
                    ->orderBy(Pricing\Entity::ID, 'desc')
                    ->get();
    }

    public function getGatewayPricingPlans()
    {
        return $this->newQuery()
                    ->whereNotNull(Pricing\Entity::GATEWAY)
                    ->orderBy(Pricing\Entity::ID, 'desc')->get();
    }

    public function getPricingPlanByName($name)
    {
        return $this->newQuery()
                    ->where(Pricing\Entity::PLAN_NAME, '=', $name)
                    ->orderBy(Pricing\Entity::ID, 'desc')
                    ->get();
    }

    public function getPricingPlanRule($id)
    {
        return $this->newQuery()->findOrFailPublic($id);
    }

    public function deletePlanRule($planId, $ruleId)
    {
        $rule = $this->newQuery()
                     ->planId($planId)
                     ->where(Entity::ID, '=', $ruleId)
                     ->firstOrFailPublic();

        $count = $rule->payments->count();

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
        $rule = $this->newQuery()
                     ->planId($planId)
                     ->where(Entity::ID, '=', $ruleId)
                     ->firstOrFailPublic();

        $count = $rule->payments->count();

        if ($count === 0)
        {
            return $this->forceDelete($rule);
        }
        else
        {
            return $this->delete($rule);
        }
    }
}
