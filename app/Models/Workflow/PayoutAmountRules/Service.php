<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Exception;
use RZP\Models\Base;
use \RZP\Models\Payout;
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
     * @param array $input
     * @return array
     */
    public function getWorkflowPayoutAmountRules(array $input): array
    {
        $merchantId = $this->merchant->getId();

        $amountRules =  $this->repo
                             ->workflow_payout_amount_rules
                             ->fetch($input, $merchantId);

        $amountRules = $amountRules->sortBy(Entity::MIN_AMOUNT);

        $amountRules = $amountRules->values();

        return $amountRules->toArrayPublic();
    }

    /**
     * Gets merchant ids which have a workflow with create_payout (or any other specified) permission
     *
     * @param $input
     * @return array
     */
    public function getMerchantIdsForCreatePayoutWorkflowPermission(array $input): array
    {
        $orgId = $this->auth->getOrgId();

        Org\Entity::verifyIdAndStripSign($orgId);

        (new Validator())->validateInput('fetch_merchant_id', $input);

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
    public function createWorkflowPayoutAmountRules(array $input): array
    {
        $this->trace->info(TraceCode::WORKFLOW_PAYOUT_RULES_ATTACHMENT, $input);

        (new Validator())->validateInput('create_rules_multiple', $input);

        $rules = $input[Entity::RULES] ?? [];

        $merchantId = $this->merchant->getId();

        $orgId = $this->auth->getOrgId();

        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $params = [];

        /** @var Base\PublicCollection $wfPayoutAmountRules */
        $wfPayoutAmountRules = $this->repo->workflow_payout_amount_rules->fetch($params, $merchantId);

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

        $workflowIdsFromInput = array_filter(array_column($rules, Entity::WORKFLOW_ID));

        // Fetch workflows with create_payout permission
        $workflows = $this->repo->workflow->getWorkflowsForPermissionNameAndCategory(Name::CREATE_PAYOUT,
                                                                     Category::PAYOUTS,
                                                                                      $orgId,
                                                                                      $merchantId);

        $workflowIds = $workflows->pluck(Entity::ID)->toArray();

        Workflow\Entity::verifyIdAndSilentlyStripSignMultiple($workflowIdsFromInput);

        // Taking diff of workflow ids from input and from database. If there are extra workflow ids in input
        // it means that some workflow ids provided in input are invalid.
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

        $payoutAmountRules = $this->core()->create($rules, $this->merchant);

        $response =(new Payout\Service())->migrateOldConfigToNewOnes(['merchant_ids' => [$this->merchant->getId()]]);

        $this->trace->info(TraceCode::WFS_CONFIG_SYNC_ATTEMPT_RESPONSE_FOR_CONFIG_CREATE, $response);

        return $payoutAmountRules->toArrayPublic();
    }

    /**
     * Payout workflow edit
     *
     * @param array $input
     * @return array
     */
    public function editWorkflowPayoutAmountRules(array $input): array
    {
        $this->trace->info(TraceCode::WORKFLOW_PAYOUT_EDIT, $input);

        (new Validator())->validateInput('edit_payout_workflow', $input);

        $editWorkflows = $input[Entity::WORKFLOWS] ?? [];

        $orgId = $this->auth->getOrgId();

        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $payoutAmountRules = $this->core()->edit($editWorkflows, $this->merchant);

        $response = (new Payout\Service())->migrateOldConfigToNewOnes(['merchant_ids' => [$this->merchant->getId()]]);

        $this->trace->info(TraceCode::WFS_CONFIG_SYNC_ATTEMPT_RESPONSE_FOR_CONFIG_EDIT, $response);

        return $payoutAmountRules->toArrayPublic();
    }
}
