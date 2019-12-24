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

    public function create($rules, $merchantId): array
    {
        // Insert all rules together into database
        $insertedPayoutAmountRules = $this->repo->transaction( function() use ($rules, $merchantId){

            $insertedPayoutAmountRules = new Base\PublicCollection();

            foreach($rules as $rule)
            {
                $rule[Entity::MERCHANT_ID] = $merchantId;

                $payoutAmountRules = new Entity();

                $payoutAmountRules->build($rule);

                $insertedPayoutAmountRules->push($payoutAmountRules);

                $this->repo->saveOrFail($payoutAmountRules);
            }

            return $insertedPayoutAmountRules;
        });

        return $insertedPayoutAmountRules->toArrayWithItems();
    }

    public function getWorkflowRules($merchantId = null)
    {
        // If merchant id is passed through proxyAuth and not url
        if ($this->merchant)
        {
            $merchantId = $this->merchant->getId();
        }

        $amountRules = $this->repo
                            ->workflow_payout_amount_rules
                            ->fetchWorkflowRulesForMerchant($merchantId);

        if ($this->app['basicauth']->isProxyAuth())
        {
            // An existing api returns in this format for proxyAuth which is maintained
            return $amountRules->toArrayPublic();
        }
        elseif ($this->app['basicauth']->isAdminAuth())
        {
            // Returns in a format including containing more fields like id in database useful for admin
            return $amountRules->toArrayWithItems();
        }
    }
}
