<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function getWorkflowRules($merchantId = null): array
    {
        return $this->core()->getWorkflowRules($merchantId);
    }

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
