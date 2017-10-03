<?php

namespace RZP\Models\Pricing;

use RZP\Models\Base;
use RZP\Models\Pricing;
use RZP\Models\Admin\Action;

class Core extends Base\Core
{
    public function addPlanRule(Plan $plan, array $input): Entity
    {
        $rule = (new Entity)->addPlanRule($input, $plan);

        $rule = $rule->generateId();

        $rule->getValidator()->validateRuleDoesNotMatch($plan);

        $rule->setAuditAction(Action::CREATE_PRICING_PLAN_RULE);

        $this->repo->saveOrFail($rule);

        return $rule;
    }

    /**
     * Create a pricing plan from rule input
     * The $planName is sent separately
     */
    public function createPlan(string $planName, array $input): Plan
    {
        $input[Entity::PLAN_NAME] = $planName;

        $rule = (new Entity)->build($input);

        $rule = $rule->generateId();

        $plan = $this->repo->pricing->getPricingPlanByName($input[Entity::PLAN_NAME]);

        Pricing\Validator::validatePlanCountZero($plan);

        $rule->setAuditAction(Action::CREATE_MERCHANT_PRICING_PLAN);

        $this->repo->saveOrFail($rule);

        return $this->createPlanFromRule($rule);
    }

    public function createBulkPricing(array $input)
    {
        (new Validator())->validateInput('createBulkPricing', $input);

        $planName = $input[Entity::PLAN_NAME];

        $inputRules = $input[Entity::RULES];

        $this->repo->transactionOnLiveAndTest(function() use ($planName, $inputRules)
        {
            $plan = $this->createPlan($planName, $inputRules[0]);

            array_shift($inputRules);

            foreach ($inputRules as $inputRule)
            {
                $rule = $this->addPlanRule($plan, $inputRule);

                $plan->add($rule);
            }

            // $rules = $plan->all();

            // $rules to be injected in workflow here
        });
    }

    protected function createPlanFromRule(Entity $rule): Plan
    {
        $plan = new Plan([$rule]);

        return $plan;
    }
}
