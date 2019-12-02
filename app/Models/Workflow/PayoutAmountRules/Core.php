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

    public function create($rules): array
    {
        $merchantId = $this->merchant->getId();

        // Insert all rules together into database
        $this->repo->transaction( function() use ($rules, $merchantId){
            foreach($rules as $rule)
            {
                $rule[Entity::MERCHANT_ID] = $merchantId;
                $payoutAmountRules = new Entity();
                $payoutAmountRules->build($rule);

                $this->repo->saveOrFail($payoutAmountRules);
            }
        });

        // Return inserted elements
        $response = $this->repo->workflow_payout_amount_rules->fetchWorkflowRulesForMerchant($merchantId)->toArrayAdmin();

        return $response;
    }

    public function getWorkflowRules($merchantId = null)
    {
        // Assuming admin auth initially
        $auth = 'admin';

        // If merchant id is passed through proxyAuth and not url
        if($this->merchant)
        {
            $merchantId = $this->merchant->getId();
            $auth = 'proxy';
        }

        $amountRules = $this->repo
            ->workflow_payout_amount_rules
            ->fetchWorkflowRulesForMerchant($merchantId);

        if($auth == 'proxy')
        {
            // An existing api returns in this format for proxyAuth which is maintained
            return $amountRules->toArrayPublic();
        }
        else
        {
            // Returns in a format including containing more fields like id in database
            return $amountRules->toArrayWithItems();
        }
    }

    public function getAllWorkflowRulesForOrg($limit, $offset, $merchantId, $orgId)
    {
        return $this->repo
            ->workflow_payout_amount_rules
            ->fetchAllWorkflowRulesForOrg($limit, $offset, $merchantId, $orgId)
            ->toArrayWithItems();
    }
}
