<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Models\Workflow\Base;

class Repository extends Base\Repository
{
    protected $entity = 'workflow_payout_amount_rules';

    public function fetchWorkflowRulesForMerchant(string $merchantId)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->get();
    }
}
