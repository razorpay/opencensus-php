<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Admin\Org;

class Service extends Base\Service
{
    // Gets workflow rules for a single merchant whose id is passed through proxyAuth or url in adminAuth
    public function getWorkflowRules($merchantId = null): array
    {
        return $this->core()->getWorkflowRules($merchantId);
    }

    // Gets workflow rules for all merchants
    public function getAllWorkflowRules($limit, $offset, $merchantId)
    {
        $orgId = $this->auth->getOrgId();

        Org\Entity::verifyIdAndStripSign($orgId);

        return $this->core()->getAllWorkflowRulesForOrg($limit, $offset, $merchantId, $orgId);
    }

    public function createWorkflowPayoutAmountRules($input): array
    {
        $rules = $input['rules'];
        $merchantId = $this->merchant->getId();

        // Check if workflow exists and belongs to merchant in context
        foreach ($rules as $rule)
        {
            $workflow = $this->repo->workflow->findOrFailPublic($rule[Entity::WORKFLOW_ID]);

            $permissionNames = (array_column($workflow->permissions->toArrayPublic()['items'], 'name'));

            if (in_array('create_payout', $permissionNames, true) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_WORKFLOW_FOR_PAYOUT,
                    null,
                    ['id' => $rule[Entity::WORKFLOW_ID]]);
            }

            if($merchantId != $workflow->toArray()[Entity::MERCHANT_ID])
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_WORKFLOW_NOT_ACCESSIBLE,
                    null,
                    ['id' => $rule[Entity::WORKFLOW_ID]]);
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

        $result = $this->core()->create($rules);
        return $result;
    }
}
