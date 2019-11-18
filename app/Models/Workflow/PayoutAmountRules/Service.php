<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function getWorkflowRules(): array
    {
        $merchantId = $this->merchant->getId();

        $amountRules = $this->repo
                            ->workflow_payout_amount_rules
                            ->fetchWorkflowRulesForMerchant($merchantId);

        return $amountRules->toArrayPublic();
    }

    public function createWorkflowPayoutAmountRules($input): array
    {
        $result = $this->core()->createWorkflowPayoutAmountRules($input);
        return $result;
    }
}
