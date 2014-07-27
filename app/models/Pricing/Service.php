<?php

namespace Models\Pricing;

use Pricing;

class Service extends Base\Service
{
    protected $repo = null;

    public function __construct()
    {
        $this->repo = new Pricing\Repository();
    }

    public function createPlan($input)
    {
        Pricing\Validator::createPlanValidate($input);

        $pricing = new Pricing($input);

        $pricing->newPlan();

        $this->repo->save($pricing);

        return $pricing->toArray();
    }

    public function addPlanRule($id, $input)
    {
        $plan = $this->repo->getPlan($id);

        Pricing\Validator::addPlanRuleValidate($plan, $input);

        $pricing->fill($input);

        $pricing->save();
    }

    public function deletePlanRule($planId, $ruleId)
    {
        $this->core->checkPlanId($id);

        $this->core->deletePlanRule($ruleId);
    }

    public function replacePlanRule($input)
    {
        $this->core->checkPlanId($id);

        $this->replacePlanRule($ruleId);
    }

    public function deletePlan($input)
    {
        ;
    }
}