<?php

namespace Models\Pricing;

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

        $pricing = new Pricing\Entity($input);

        $pricing->newPlan();

        $this->repo->save($pricing);

        return $pricing->toArray();
    }

    public function addPricingPlanRule($id, $input)
    {
        $plan = $this->repo->getPricingPlan($id);

        Pricing\Validator::addPlanRuleValidate($plan, $input);

        $rule = new Pricing\Entity();

        $rule->fillRule($input, $plan);

        (new Pricing\Repository)->save($rule);

        return $rule->toArray();
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