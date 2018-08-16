<?php

namespace RZP\Models\Pricing;

use RZP\Models\Base;
use RZP\Models\Admin\Action;

class Core extends Base\Core
{
    public function addPlanRule(Plan $plan, array $input): Entity
    {
        $rule = (new Entity)->addPlanRule($input, $plan);

        $rule = $rule->generateId();

        $rule->getValidator()->validateRuleDoesNotMatch($plan);

        $rule->setAuditAction(Action::CREATE_PRICING_PLAN_RULE);

        $this->app['workflow']
            ->setEntityAndId($rule->getEntity(), $rule->getPlanId())
            ->handle((new \stdClass), $rule);

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

        $rule->setAuditAction(Action::CREATE_MERCHANT_PRICING_PLAN);

        $this->repo->saveOrFail($rule);

        return $this->createPlanFromRule($rule);
    }

    /**
     * Duplicate the existing rule, update its properties from input and create it as a new rule.
     * Soft delete the previous rule.
     */
    public function editPlanRule(String $planId, String $ruleId, array $input): Entity
    {
        $rule = $this->repo->pricing->getPricingPlanRule($planId, $ruleId);

        $newRule = $rule->replicate();

        $plan = $this->repo->pricing->getPricingPlanById($planId);

        $planWithoutOldRule = $plan->reject(function($existingRule) use ($rule) {
            return $existingRule->getId() === $rule->getId();
        });

        $newRule->edit($input, 'editPlanRule');

        $newRule = $newRule->generateId();

        $newRule->getValidator()->validateRuleDoesNotMatch($planWithoutOldRule);

        $newRule->setAuditAction(Action::CREATE_UPDATE_PRICING_PLAN_RULE);

        $this->app['workflow']
             ->setEntityAndId($rule->getEntity(), $planId)
             ->handle($rule, $newRule);

        $newRule = $this->repo->transactionOnLiveAndTest(function() use ($rule, $newRule)
        {
            $this->repo->pricing->deletePlanRuleForce($rule->getPlanId(), $rule->getId());

            $this->repo->saveOrFail($newRule);

            return $newRule;
        });

        return $newRule;
    }

    public function createPricing(array $input)
    {
        $validator = new Validator();

        $validator->validateInput('createBulkPricing', $input);

        $planName = $input[Entity::PLAN_NAME];

        $inputRules = $input[Entity::RULES];

        // Validate plan name is unique
        $plan = $this->repo->pricing->getPricingPlanByName($planName);

        $validator->validatePlanCountZero($plan);

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
