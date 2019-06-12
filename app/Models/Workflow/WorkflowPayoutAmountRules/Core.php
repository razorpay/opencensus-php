<?php

namespace RZP\Models\Workflow\WorkflowPayoutAmountRules;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function getWorkflowRulesForMerchant(string $merchantId)
    {
       return $this->repo->workflow_payout_amount_rules->fetchWorkflowRulesForMerchant($merchantId);
    }
}
