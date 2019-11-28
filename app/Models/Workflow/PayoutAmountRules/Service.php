<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Error\ErrorCode;
use RZP\Exception;
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
        $rules = $input['rules'];
        $merchantId = $this->merchant->getId();

        // Check if workflow belongs to merchant in context
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

        (new Entity())->getValidator()->ensureDistinctWorkflowIds($rules);

        $result = $this->core()->createWorkflowPayoutAmountRules($rules);
        return $result;
    }
}
