<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function getWorkflowRules($merchantId = null): array
    {
        // Assuming admin auth initially
        $auth = 'admin';

        // If merchant id is passed through headers and not url
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
            return $amountRules->toArrayPublic();
        }
        else
        {
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

    public function createWorkflowPayoutAmountRules($input): array
    {
        $result = $this->core()->createWorkflowPayoutAmountRules($input);
        return $result;
    }
}
