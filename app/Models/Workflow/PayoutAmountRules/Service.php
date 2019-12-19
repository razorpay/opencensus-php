<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Permission\Name;
use RZP\Models\Base\PublicCollection;

class Service extends Base\Service
{
    // Gets workflow rules for a single merchant whose id is passed through proxyAuth or url in adminAuth
    public function getWorkflowRules($merchantId = null): array
    {
        return $this->core()->getWorkflowRules($merchantId);
    }

    // Gets workflow rules for all merchants
    public function getMerchantIdsForWorkflowPermission($input)
    {
        $orgId = $this->auth->getOrgId();

        Org\Entity::verifyIdAndStripSign($orgId);

        $results = $this->repo->workflow_payout_amount_rules->getMerchantIdsForWorkflowPermission($orgId, $input);

        $results = (new PublicCollection($results));

        return $results->toArrayWithItems();
    }

    public function createWorkflowPayoutAmountRules($input): array
    {
        $rules = $input['rules'];

        $merchantId = null;

        // Check if workflow exists and belongs to merchant in context
        for($index = 0; $index < count($rules); $index++)
        {

            \RZP\Models\Workflow\Entity::verifyIdAndStripSign($rules[$index][Entity::WORKFLOW_ID]);

            $rule = $rules[$index];

            $workflow = $this->repo->workflow->findOrFailPublic($rule[Entity::WORKFLOW_ID]);

            $workflowPermissionsArray = $workflow->permissions->toArrayPublic();

            $permissionNames = (array_column($workflowPermissionsArray['items'], 'name'));

            // Ensure that given workflows have create_payout permission
            if (in_array(Name::CREATE_PAYOUT, $permissionNames, true) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_WORKFLOW_FOR_PAYOUT,
                    null,
                    ['id' => $rule[Entity::WORKFLOW_ID]]);
            }

            $workflowsArray = $workflow->toArray();

            // Ensure that all merchants are the same
            if((empty($merchantId) === false) and ($merchantId !== $workflowsArray[Entity::MERCHANT_ID]))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_WORKFLOW_NOT_ACCESSIBLE,
                    null,
                    ['id' => $rule[Entity::WORKFLOW_ID]]);
            }
            elseif (empty($merchantId) === true)
            {
                $merchantId = $workflowsArray[Entity::MERCHANT_ID];
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

        $result = $this->core()->create($rules, $merchantId);
        return $result;
    }
}
