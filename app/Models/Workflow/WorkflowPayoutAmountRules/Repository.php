<?php

namespace RZP\Models\Workflow\WorkflowPayoutAmountRules;

use RZP\Models\Workflow\Base;

class Repository extends Base\Repository
{
    protected $entity = 'workflow_payout_amount_rules';

    public function fetchWorkflowRulesForMerchant(
        int $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)->get();
    }
}
