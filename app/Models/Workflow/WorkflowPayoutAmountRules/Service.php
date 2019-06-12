<?php

namespace RZP\Models\Workflow\WorkflowPayoutAmountRules;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function getWorkflowRules()
    {
        $merchantId = $this->merchant->getId();

        $amountRules = $this->core()->getWorkflowRulesForMerchant($merchantId);

        return $amountRules->toArrayPublic();
    }
}
