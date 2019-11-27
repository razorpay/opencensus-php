<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Models\Base;

class Service extends Base\Service
{
    // Gets workflow rules for a single merchant whose id is passed through proxyAuth or url in adminAuth
    public function getWorkflowRules($merchantId = null): array
    {
        return $this->core()->getWorkflowRules($merchantId);
    }

    // Gets workflow rules for all merchants
    public function getAllWorkflowRules($limit, $offset)
    {
        return $this->core()->getAllWorkflowRulesWithPaginationLinks($limit, $offset);
    }

    public function createWorkflowPayoutAmountRules($input): array
    {
        $result = $this->core()->createWorkflowPayoutAmountRules($input);
        return $result;
    }
}
