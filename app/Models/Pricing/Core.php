<?php

namespace RZP\Models\Pricing;

use RZP\Models\Base;
use RZP\Models\Admin\Action;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Permission\Name as PermissionName;

class Core extends Base\Core
{
    /**
     * Add a rule to a pricing plan.
     *
     * @param Plan   $plan
     * @param array  $input
     * @param string $ruleOrgId
     *
     * @return Entity
     */
    public function addPlanRule(Plan $plan, array $input, string $ruleOrgId = null): Entity
    {
        $this->trace->info(TraceCode::PRICING_PLAN_RULE_ADD_ATTEMPT,
            $this->redactSensitiveInfoFromLogs($input));

        $rule = (new Entity)->addPlanRule($input, $plan);

        $rule = $rule->generateId();

        $ruleOrgId = Entity::stripDefaultSign($ruleOrgId);

        $rule->setAttribute(Entity::ORG_ID, $ruleOrgId);

        $rule->getValidator()->validateRuleDoesNotMatch($plan);

        $rule->getValidator()->validateTypeMatch($plan);

        $rule->getValidator()->validateRuleForFeeBearer($plan, $rule);

        $rule->getValidator()->validatePlanTypeForOrg();

        $rule->setAuditAction(Action::CREATE_PRICING_PLAN_RULE);

        $this->app['workflow']
            ->setEntityAndId($rule->getEntity(), $rule->getPlanId())
            ->handle((new \stdClass), $rule);

        $this->repo->saveOrFail($rule);

        $this->trace->info(TraceCode::PRICING_PLAN_RULE_ADD_SUCCESS,
            $this->redactSensitiveInfoFromLogs($rule->toArray()));

        return $rule;
    }

    /**
     * Create a pricing plan from rule input
     * The $planName is sent separately
     *
     * @param string $planName
     * @param array  $input
     * @param string $ruleOrgId
     *
     * @return Plan
     */
    public function createPlan(string $planName, array $input, string $ruleOrgId = null): Plan
    {
        $input[Entity::PLAN_NAME] = $planName;

        $rule = (new Entity)->build($input);

        $rule = $rule->generateId();

        $ruleOrgId = Entity::stripDefaultSign($ruleOrgId);

        $rule->setAttribute(Entity::ORG_ID, $ruleOrgId);

        $rule->getValidator()->validatePlanTypeForOrg();

        $rule->setAuditAction(Action::CREATE_MERCHANT_PRICING_PLAN);

        $this->repo->saveOrFail($rule);

        return $this->createPlanFromRule($rule);
    }

    /**
     * Duplicate the existing rule, update its properties from input and create it as a new rule.
     * Soft delete the previous rule.
     */
    public function editPlanRule(String $planId, String $ruleId, array $input, String $orgId = null): Entity
    {
        $this->trace->info(
            TraceCode::PRICING_PLAN_RULE_UPDATE_ATTEMPT,
            ['id' => $ruleId]);

        $rule = $this->repo->pricing->getPlanRule($planId, $ruleId, $orgId);

        $newRule = $rule->replicate();

        $plan = $this->repo->pricing->getPlan($planId);

        $planWithoutOldRule = $plan->reject(function($existingRule) use ($rule) {
            return $existingRule->getId() === $rule->getId();
        });

        $newRule->edit($input, 'editPlanRule');

        $newRule = $newRule->generateId();

        $newRule->getValidator()->validateRuleDoesNotMatch($planWithoutOldRule);

        $newRule->getValidator()->validateRuleForFeeBearer($plan, $newRule);

        $newRule->setAuditAction(Action::CREATE_UPDATE_PRICING_PLAN_RULE);

        $this->app['workflow']
             ->setEntityAndId($rule->getEntity(), $planId)
             ->handle($rule, $newRule);

        $newRule = $this->repo->transactionOnLiveAndTest(function() use ($rule, $newRule, $orgId)
        {
            $this->repo->pricing->deletePlanRuleForce($rule->getPlanId(), $rule->getId(), $orgId);

            $this->repo->saveOrFail($newRule);

            return $newRule;
        });

        $this->trace->info(
            TraceCode::PRICING_PLAN_RULE_UPDATE_SUCCESS,
            [$this->redactSensitiveInfoFromLogs($rule->toArray())]);

        return $newRule;
    }

    public function fetchEligiblePlansWithMissingCorporateRule(int $limit ):array
    {
        return $this->repo->pricing->fetchEligiblePlanIdsWithMissingCorporateRule($limit);
    }

    public function create(array $input, string $ruleOrgId)
    {
        $validator = new Validator();

        $validator->validateInput('createBulkPricing', $input);

        $planName = $input[Entity::PLAN_NAME];

        $inputRules = $input[Entity::RULES];

        // Validate plan name is unique
        $plan = $this->repo->pricing->withBuyPricing()->getPlanByName($planName);

        $validator->validatePlanCountZero($plan);

        $plan = $this->repo->transactionOnLiveAndTest(function() use ($planName, $inputRules, $ruleOrgId)
        {
            $this->trace->info(TraceCode::PRICING_PLAN_CREATE_ATTEMPT,$this->redactSensitiveInfoFromLogs($inputRules[0]));

            $plan = $this->createPlan($planName, $inputRules[0], $ruleOrgId);

            array_shift($inputRules);

            foreach ($inputRules as $inputRule)
            {
                $rule = $this->addPlanRule($plan, $inputRule, $ruleOrgId);

                $plan->add($rule);
            }

            return $plan;

            // $rules = $plan->all();

            // $rules to be injected in workflow here
        });

        return $plan;
    }

    private function redactSensitiveInfoFromLogs(array $input)
    {
        if (isset($input[Entity::TYPE]) and $input[Entity::TYPE] === Type::BUY_PRICING)
        {
            unset($input[Entity::FIXED_RATE], $input[Entity::PERCENT_RATE]);
        }

        return $input;
    }

    protected function createPlanFromRule(Entity $rule): Plan
    {
        $plan = new Plan([$rule]);

        return $plan;
    }
}
