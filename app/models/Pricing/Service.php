<?php

namespace Models\Pricing;

use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;
use Models\Pricing;

class Service extends Base\Service
{
    protected $repo = null;

    public function __construct()
    {
        $this->repo = new Pricing\Repository();
    }

    public function createPricingPlan($input)
    {
        Pricing\Validator::createPlanValidate($input);

        $plans = $this->repo->getPricingPlanByName($input[Entity::PLAN_NAME]);

        if ($plans->count() > 0)
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_PRICING_PLAN_WITH_SAME_NAME_EXISTS);

        $pricing = new Pricing\Entity($input);

        $pricing->newPlan();

        $this->repo->saveOrFail($pricing);

        $plan = new Plan(array($pricing));

        return $plan->toArrayPublic();
    }

    public function addPricingPlanRule($id, $input)
    {
        $plan = $this->repo->getPricingPlanByIdOrFailPublic($id);

        Pricing\Validator::addPlanRuleValidate($plan, $input);

        $rule = new Pricing\Entity();

        $rule->fillRule($input, $plan);

        (new Pricing\Repository)->saveOrFail($rule);

        return $rule->toArray();
    }

    public function getPricingPlanById($id)
    {
        $pricingPlan = $this->repo->getPricingPlanById($id);

        return $pricingPlan->toArrayPublic();
    }

    public function getPricingPlans()
    {
        $pricingPlans = $this->repo->getPricingPlans();

        return $pricingPlans->toArrayMultiplePlansPublic();
    }

    public function getMerchantPricingPlans()
    {
        $pricingPlans = $this->repo->getMerchantPricingPlans();

        return $pricingPlans->toArrayMultiplePlansPublic();
    }

    public function getGatewayPricingPlans()
    {
        $pricingPlans = $this->repo->getGatewayPricingPlans();

        return $pricingPlans->toArrayMultiplePlansPublic();
    }

    public function deletePricingPlanRule($planId, $ruleId)
    {
        $this->core->checkPlanId($id);

        $this->core->deletePlanRule($ruleId);
    }

    public function replacePricingPlanRule($input)
    {
        $this->core->checkPlanId($id);

        $this->replacePlanRule($ruleId);
    }

    public function deletePricingPlan($input)
    {
        ;
    }
}