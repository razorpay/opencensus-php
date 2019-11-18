<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function fetchWorkflowForMerchantIfDefined(int $amount, Merchant\Entity $merchant)
    {
        $rules = $this->repo->workflow_payout_amount_rules->fetchWorkflowRulesForMerchant($merchant->getId());

        //
        // Filter the rules to match exactly one and return if found
        // If no rules were defined, or no match was found, return null
        //
        /** @var Entity|null $workflowRule */
        $workflowRule = $this->filterRulesByAmount($rules, $amount);

        if ($workflowRule !== null)
        {
            return $workflowRule->workflow;
        }

        return null;
    }

    protected function filterRulesByAmount(Base\PublicCollection $rules, int $amount)
    {
        $evaluatedRule = null;

        /** @var Entity $rule */
        foreach ($rules as $rule)
        {
            $minAmount = $rule->getMinAmount();
            $maxAmount = $rule->getMaxAmount();

            if (($minAmount === null) and
                ($maxAmount === null))
            {
                $evaluatedRule = $rule;

                break;
            }

            $maxAmount = $maxAmount ?: PHP_INT_MAX;

            if (($minAmount < $amount) and ($maxAmount >= $amount))
            {
                $evaluatedRule = $rule;

                break;
            }
        }

        return $evaluatedRule;
    }

    public function createWorkflowPayoutAmountRules($input): array
    {
        $payoutAmountRules = new Entity();
        $payoutAmountRules->create($input);
        return $input;
    }
}
