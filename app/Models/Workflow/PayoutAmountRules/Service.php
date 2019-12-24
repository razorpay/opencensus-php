<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Permission\Name;
use RZP\Models\Base\PublicCollection;

class Service extends Base\Service
{
    /**
     * Gets workflow rules for a single merchant whose id is passed through proxyAuth or url in adminAuth
     *
     * @param string $merchantId
     * @return array
     */
    public function getWorkflowRules($merchantId = null): array
    {
        return $this->core()->getWorkflowRules($merchantId);
    }

    /**
     * Gets merchant ids which have a workflow with create_payout (or any other specified) permission
     *
     * @param string $merchantId
     * @return array
     */
    public function getMerchantIdsForWorkflowPermission($input)
    {
        $orgId = $this->auth->getOrgId();

        Org\Entity::verifyIdAndStripSign($orgId);

        $results = $this->repo->workflow_payout_amount_rules->getMerchantIdsForWorkflowPermission($orgId, $input);

        $results = (new PublicCollection($results));

        return $results->toArrayWithItems();
    }

    /**
     * Creation of Payout amount rules
     *
     * @param array $input
     * @return array
     */
    public function createWorkflowPayoutAmountRules($input): array
    {
        $rules = $input[Entity::RULES];

        $merchantId = null;

        $this->trace->info(TraceCode::WORKFLOW_PAYOUT_RULES_ATTACHMENT, $input);

        // The following loop will check for each of the rules whether :
        // 1. That all workflow ids exist and have create_payout permission
        // 2. That all workflow ids have the same merchant id.
        for ($index = 0; $index < count($rules); $index++)
        {

            \RZP\Models\Workflow\Entity::verifyIdAndStripSign($rules[$index][Entity::WORKFLOW_ID]);

            $rule = $rules[$index];

            $workflow = $this->repo->workflow->findOrFailPublic($rule[Entity::WORKFLOW_ID]);

            $workflowPermissionsArray = $workflow->permissions->toArray();

            $permissionNames = (array_column($workflowPermissionsArray, 'name'));

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
            if ((empty($merchantId) === false) and ($merchantId !== $workflowsArray[Entity::MERCHANT_ID]))
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
        if (!empty($this->repo->workflow_payout_amount_rules->fetchWorkflowRulesForMerchant($merchantId)->toArray()))
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
