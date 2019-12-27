<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Models\Base;
use RZP\Models\Workflow;
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

    /**
     * @param array $rules
     * @param Merchant\Entity $merchant
     * @return Base\PublicCollection
     */
    public function create(array $rules, Merchant\Entity $merchant)
    {
        // Insert all rules together into database
        $insertedPayoutAmountRules = $this->repo->transaction( function() use ($rules, $merchant)
        {
            $insertedPayoutAmountRules = new Base\PublicCollection();

            foreach ($rules as $rule)
            {
                Workflow\Entity::verifyIdAndStripSign($rule[Entity::WORKFLOW_ID]);

                $rule[Entity::MERCHANT_ID] = $merchant->getId();

                $payoutAmountRules = new Entity();

                $payoutAmountRules->build($rule);

                $insertedPayoutAmountRules->push($payoutAmountRules);

                $this->repo->saveOrFail($payoutAmountRules);
            }

            return $insertedPayoutAmountRules;
        });

        return $insertedPayoutAmountRules;
    }
}
