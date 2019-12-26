<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Workflow;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Permission\Name;

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

        $results = $this->repo->workflow_payout_amount_rules->getMerchantIdsForCreatePayoutWorkflowPermission($orgId,
                                                                                                              $input);

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

        $validator = new Validator();

        $validator->checkForValidAmountRanges($rules);

        $validator->ensureDistinctWorkflowIds($rules);

        $merchantId = $this->merchant->getId();

        $orgId = $this->auth->getOrgId();

        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $this->trace->info(TraceCode::WORKFLOW_PAYOUT_RULES_ATTACHMENT, $input);

        /** @var Entity $wfPayoutAmountRules */
        $wfPayoutAmountRules = $this->repo->workflow_payout_amount_rules->fetchWorkflowRulesForMerchant($merchantId);

        // Ensure that workflow_payout_amount_rules rules do not exist already
        // We fail here because editing existing workflow_payout_amount_rules could cause conflicts with the new ones
        // Alternative is to delete and create new workflow and attach workflow_payout_amount_rules to the new workflow
        if ($wfPayoutAmountRules->count() > 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_RULES_UPDATE_OR_DELETE_NOT_ALLOWED,
                null,
                [
                    'merchant_id'   => $merchantId,
                    'input'         => $input,
                ]);
        }

        $workflowIdsFromInput = array_column($rules, Entity::WORKFLOW_ID);

        // Fetch workflows with create_payout permission
        $workflows = (new Workflow\Action\Core)->getWorkflowsForPermission(Name::CREATE_PAYOUT,
                                                                           $orgId,
                                                                           $merchantId);

        $workflowIds = $workflows->pluck(Entity::ID)->toArray();

        Workflow\Entity::verifyIdAndStripSignMultiple($workflowIdsFromInput);

        $diff = array_diff($workflowIdsFromInput, $workflowIds);

        if (count($diff) > 0)
        {
            throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_WORKFLOW_FOR_PAYOUT,
                    null,
                    [
                        'merchant_id'   => $merchantId,
                        'diff'          => $diff
                    ]);
        }

        $result = $this->core()->create($rules, $this->merchant);

        return $result->toArrayWithItems();
    }
}
