<?php

namespace RZP\Models\Workflow\WorkflowPayoutAmountRules;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function getWorkflowRulesForMerchant(string $merchantId): array
    {
       $this->repo->fetchWorkflowRulesForMerchant($merchantId);
    }
}
