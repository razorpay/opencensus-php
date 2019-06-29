<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function fetchWorkflowForMerchantIfDefined(string $payoutId, Merchant\Entity $merchant)
    {
        /** @var Payout\Entity $payout */
        $payout = $this->repo->payout->findByIdAndMerchant($payoutId, $merchant);

        $rules = $this->repo->workflow_payout_amount_rules->fetchWorkflowRulesForMerchant($merchant->getId());

        //
        // Filter the rules to match exactly one and return if found
        // If no rules were defined, or no match was found, return null
        //
        $amount = $payout->getAmount();

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
            if (($rule->getMinAmount() < $amount) and
                ($rule->getMaxAmount() >= $amount))
            {
                $evaluatedRule = $rule;

                break;
            }
        }

        return $evaluatedRule;
    }
}
