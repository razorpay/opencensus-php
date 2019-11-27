<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Error\ErrorCode;
use RZP\Exception;
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
        $rules = $input['rules'];
        $merchantId = $this->merchant->getId();

        // Check if workflow belongs to merchant in context through proxyAuth
        foreach ($rules as $rule)
        {
            $workflow = $this->repo->workflow->findOrFailPublic($rule['workflow_id'])->toArray();

            if($merchantId != $workflow['merchant_id'])
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_WORKFLOW_NOT_ACCESSIBLE,
                    null,
                    ['id' => $rule['workflow_id']]);
            }
        }

        // Ensure that workflow rules do not exist already
        if(!empty($this->repo->workflow_payout_amount_rules->fetchWorkflowRulesForMerchant($merchantId)->toArray()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_RULES_UPDATE_OR_DELETE_NOT_ALLOWED,
                null,
                ['id' => $merchantId]);
        }

        (new Entity())->getValidator()->checkForValidAmountRanges($rules);

        // Insert all rules together into database
        $this->repo->transaction( function() use ($rules, $merchantId){
            foreach($rules as $rule)
            {
                $rule['merchant_id'] = $merchantId;
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
            return $amountRules->toArrayAdmin();
        }
    }

    public function getAllWorkflowRules($limit, $offset)
    {
        return $this->repo
            ->workflow_payout_amount_rules
            ->fetchAllWorkflowRules($limit, $offset)
            ->toArray();
    }

    public function getAllWorkflowRulesWithPaginationLinks($limit, $offset)
    {
        $items = $this->getAllWorkflowRules($limit, $offset);
        $links = [];
        $total = $this->repo->workflow_payout_amount_rules->fetchTotalNumberOfElements();

        // Link to current page
        $links[] = [
            "rel"   => "self",
            "href"  => url()->full()
        ];

        // Link to first page
        $links[] = [
            "rel"   => "first",
            "href"  => url()->current()."?count=".$limit."&skip=0"
        ];

        // Link to prev page
        if($offset >= $limit)
        {
            $links[] = [
                "rel"   => "prev",
                "href"  => url()->current()."?count=".$limit."&skip=".($offset-$limit)
            ];
        }

        // Link to next page
        if($offset+$limit < $total)
        {
            $links[] = [
                "rel"   => "next",
                "href"  => url()->current()."?count=".$limit."&skip=".($offset+$limit)
            ];
        }

        // Link to last page
        $links[] = [
            "rel"   => "last",
            "href"  => url()->current()."?count=".$limit."&skip=".($total - (($total%$limit)?($total%$limit):$limit))
        ];

        // Join data items and links and send in response
        $data = [
            "items" => $items,
            "links" => $links
        ];

        return $data;
    }
}
