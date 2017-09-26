<?php

namespace RZP\Models\Pricing;

use RZP\Models\Base;
use RZP\Models\Pricing;
use RZP\Models\Admin\Action;

class Core extends Base\Core
{
    public function addPlanRule($input, Plan $plan): Entity
    {
        $rule = (new Entity)->addPlanRule($input, $plan);

        $rule = $rule->generateId();

        $rule->getValidator()->validateRuleIsUnique($plan);

        $rule->setAuditAction(Action::CREATE_PRICING_PLAN_RULE);

        $this->repo->saveOrFail($rule);

        return $rule;
    }

    /**
     * Create a pricing plan from rule input
     * The $planName is sent separately
     */
    public function createPlan(string $planName, $input): Plan
    {
        $input[Entity::PLAN_NAME] = $planName;

        $rule = (new Pricing\Entity)->build($input);

        $rule = $rule->generateId();

        $plan = $this->repo->pricing->getPricingPlanByName($input[Entity::PLAN_NAME]);

        Pricing\Validator::validatePlanCountZero($plan);

        $rule->setAuditAction(Action::CREATE_MERCHANT_PRICING_PLAN);

        $this->repo->saveOrFail($rule);

        return $this->createPlanFromRule($rule);
    }

    public function createBulkPricing($input)
    {
        (new Validator())->validatePlanInputHasRules($input);

        $planName = $input[Entity::PLAN_NAME];

        $input = $input['rules'];

        $this->repo->transactionOnLiveAndTest(function() use ($planName, $input)
        {
            $plan = $this->createPlan($planName, $input[0]);

            array_shift($input);

            foreach ($input as $value)
            {
                $rule = $this->addPlanRule($value, $plan);

                $plan->add($rule);
            }

            // $rules = $plan->all();

            // $rules to be injected in workflow here
        });
    }

    public function createPlanFromRule(Entity $rule): Plan
    {
        $plan = new Plan(array($rule));

        return $plan;
    }
}
