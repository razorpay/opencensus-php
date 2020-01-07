<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Workflow;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Permission\Name;
use RZP\Models\Admin\Permission\Category;

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
        // If merchant id is passed through proxyAuth and not url
        if ($merchantId === null)
        {
            $merchantId = $this->merchant->getId();
        }

        $relations = [];

        // If adminAuth is used retrieve additional information such as steps and roles in the workflow
        if ($this->app['basicauth']->isAdminAuth())
        {
            $relations = ['steps','steps.role'];
        }

        $amountRules =  $this->repo
                             ->workflow_payout_amount_rules
                             ->fetchWorkflowRulesForMerchant($merchantId, $relations);

        if ($this->app['basicauth']->isAdminAuth() === true)
        {
            // Returns in a format including containing more fields like id in database useful for admin
            return $amountRules->toArrayWithItems();

        }

        // An existing api returns in this format for proxyAuth which is maintained
        return $amountRules->toArrayPublic();
    }

    /**
     * Gets merchant ids which have a workflow with create_payout (or any other specified) permission
     *
     * @param string $merchantId
     * @return array
     */
    public function getMerchantIdsForCreatePayoutWorkflowPermission($input)
    {
        $orgId = $this->auth->getOrgId();

        Org\Entity::verifyIdAndStripSign($orgId);

        $validator = new Validator();

        $validator->validateInput('fetch_merchant_id', $input);

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
        // Right now rules can only be removed directly from DB
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
        $workflows = $this->repo->workflow->getWorkflowsForPermissionNameAndCategory(Name::CREATE_PAYOUT,
                                                                           $orgId,
                                                                           $merchantId,
                                                                           Category::PAYOUTS );

        $workflowIds = $workflows->pluck(Entity::ID)->toArray();

        $workflowIdsFromInput = array_filter($workflowIdsFromInput);

        Workflow\Entity::verifyIdAndSilentlyStripSignMultiple($workflowIdsFromInput);

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
