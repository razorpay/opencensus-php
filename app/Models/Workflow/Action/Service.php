<?php

namespace RZP\Models\Workflow\Action;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Admin\Role;
use RZP\Models\Workflow\Constants;
use RZP\Models\Workflow\Service as WorkflowService;
use RZP\Models\Workflow\Action\Constants as WorkflowActionConstants;
use RZP\Models\Workflow\Action\State\Entity as WorkflowActionStateEntity;

class Service extends Base\Service
{
    protected $maker;

    const ACTION_FUNCTION_MAPPING = [
        "maker" => [
            "closed"    => "getClosedActionsByMaker",
            "created"   => "getActionsByMaker",
        ],
        "checker" => [
            "requested"     => "getActionsForChecker",
            "created"       => "getActionsCheckedByAdmin",
        ],
        "super" => [
            "all"     => "getActionsByOrg",
            "open"    => "getActionsByOrg",
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        $this->maker = $this->app['workflow']->getWorkflowMaker();
    }

    public function create(array $input)
    {
        $action = $this->core()->create($input, false, $this->maker);

        return $action->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        // $duty can be maker/checker/admin_checked
        // actions will be fetched based on duty and type
        // type can be all/closed/open etc

        $duty = $input[Constants::DUTY] ?? 'default';
        $type = $input[Constants::TYPE] ?? 'all';

        if (isset(self::ACTION_FUNCTION_MAPPING[$duty][$type]))
        {
            // Function name which needs to be called to return actions based on duty and maker.
            $actionFunctionName = self::ACTION_FUNCTION_MAPPING[$duty][$type];

            unset($input[Constants::DUTY]);

            $actions = call_user_func_array([$this, $actionFunctionName], [$input]);
        }
        else
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_DUTY_TYPE_INVALID);
        }

        return $actions->toArrayPublic();
    }

    /**
     * All the checked actions by the admin.
     * @param $input
     * @param int $skip
     * @param int $count
     * @return mixed
     */
    public function getActionsCheckedByAdmin($input)
    {
        $input[Constants::EXPAND] = [Entity::MAKER, Entity::TAGGED, Entity::OWNER];

        $input[Constants::ACTIONS_CHECKED] = true;

        $actions = $this->repo->workflow_action->fetch($input);

        return $actions;
    }

    public function getActionsForRiskAudit($merchantId, $input)
    {
        $actionIds = $this->repo->workflow_action->getActionIdsForRiskAudit($merchantId, $input);

        $wfActionIds = array_map(function($id) {
            return sprintf('%s_%s', Entity::getSign(), $id);
        }, $actionIds);

        return [
            Constants::RISK_AUDIT_WORKFLOWS_FOR_QUERY           =>  Constants::getRiskAuditWorkflows(),
            Constants::WORKFLOW_ACTION_IDS                      =>  $wfActionIds,
        ];
    }

    public function getActionDetails(string $actionId)
    {
        $data = [];

        $orgId = $this->maker->getOrgId();

        Entity::verifyIdAndStripSign($actionId);

        // findByIdAndOrgId returns a collection so extracting the first element.
        // Cannot use firstorfailPublic here because findByIdAndOrgId returns collection.
        $action = $this->repo
                       ->workflow_action
                       ->getActionDetails($actionId, $orgId)
                       ->first();

        // $action can be null because we don't validate the result after fetching from the collection.
        if (empty($action) === false)
        {
            $data = $action->toArrayPublic();

            // Checkers
            $checkers = $this->repo
                             ->action_checker
                             ->fetchByActionIdWithRelations(
                                $actionId, [Entity::ADMIN]);

            $data['checkers'] = $checkers->map(function ($checker) {
                return $checker->toArrayPublic();
            })->toArray();

            // Comments
            $comments = $this->repo
                ->comment
                ->fetchByActionIdWithRelations(
                    $actionId, [Entity::ADMIN]);

            $data['comments'] = $comments->map(function ($comment)
            {
                return $comment->toArrayPublic();
            });
        }
        else
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND, null, $actionId);
        }

        return $data;
    }

    public function updateWorkflowAction(string $actionId, array $input)
    {
        Entity::verifyIdAndStripSign($actionId);

        $action = $this->repo->workflow_action->findOrFailPublic($actionId);

        $action = $this->core()->edit($action, $input);

        return $action->toArrayPublic();
    }

    public function closeAction(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $action = $this->repo->workflow_action->findOrFailPublic($id);

        $this->core()->close($action, $this->maker);

        // fetch again from db to get updated values
        $action = $this->repo->workflow_action->findOrFailPublic($id);

        return $action->toArrayPublic();
    }

    public function executeAction(string $id, Role\Entity $role = null, Base\PublicEntity $checkerEntity = null)
    {
        Entity::verifyIdAndStripSign($id);

        $action = (new Repository)->findOrFailPublic($id);

        if ($action->getApproved() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACTION_NOT_APPROVED);
        }

        if ($action->isExecuted() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACTION_ALREADY_EXECUTED);
        }

        $admin = $this->app['basicauth']->getAdmin();

        $checkerEntity = $checkerEntity ?: $admin;

        if (empty($role) === true)
        {
            if (($checkerEntity === $admin) and
                ($checkerEntity->isSuperAdmin() === false))
            {
                $role = $checkerEntity->getSuperAdminRole();
            }
            else
            {
                $role = null;
            }
        }

        return $this->core()->executeAction($action, $checkerEntity, $role);
    }

    /**
     * Get all the actions in the admin's org
     * Based on current level, get the steps/roles in the workflow
     * if the admin has the role, give the checker the action_id, step_id
     *
     * @param array $input input array
     *
     * @return array
     */
    public function getActionsForChecker(array $input)
    {
        $input[Constants::EXPAND] = [Entity::MAKER, Entity::TAGGED, Entity::OWNER];

        $input[Constants::CHECKER_ACTIONS] = true;

        $input[Entity::PERMISSION] = true;

        $actions = $this->repo->workflow_action->fetch($input);

        return $actions;
    }

    /**
     * Get actions by Org for SuperAdmin.
     *
     * @param array $input
     *
     * @return array
     */
    public function getActionsByOrg(array $input)
    {
        $input[Entity::ORG_ID] = $this->maker->getOrgId();

        $input[Constants::EXPAND] = [Entity::MAKER, Entity::TAGGED, Entity::OWNER];

        $input[Entity::PERMISSION] = true;

        $actions = $this->repo->workflow_action->fetch($input);

        return $actions;
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    public function getClosedActionsByMaker(array $input)
    {
        $input[Entity::PERMISSION] = true;

        $input[Constants::EXPAND] = [Entity::MAKER, Entity::TAGGED, Entity::OWNER];

        $input[Constants::CLOSED_ACTIONS] = true;

        $actions = $this->repo->workflow_action->fetch($input);

        return $actions;
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    public function getActionsByMaker(array $input)
    {
        $input[Entity::PERMISSION] = true;

        $input[Constants::EXPAND] = ['workflow', Entity::MAKER, Entity::TAGGED, Entity::OWNER];

        $input[Entity::ORG_ID] = $this->maker->getOrgId();

        $input[Entity::MAKER_ID] = $this->maker->getId();

        $actions = $this->repo->workflow_action->fetch($input);

        return $actions;
    }

    public function getActionsByMakerInternal(string $makerId, $input): array
    {
        $expands = ['workflow'];

        $actions = $this->repo->workflow_action->fetchActionsByMakerId($makerId, $expands, $input);

        $resp = $actions->toArrayPublic();

        foreach ($resp['items'] as $key => $value)
        {
            if ($value[Entity::STATE] == WorkflowActionStateEntity::REJECTED)
            {
                try
                {
                    [$merchant, $merchantDetails] = (new Merchant\Detail\Core())->getMerchantAndSetBasicAuth($value[Entity::MAKER_ID]);

                    $observerData = (new WorkflowService())->getWorkflowObserverData($value[Entity::ID]);

                    if (array_key_exists(WorkflowActionConstants::REJECTED_REASON, $observerData))
                    {
                        $resp['items'][$key][WorkflowActionConstants::REJECTED_REASON] = $observerData[WorkflowActionConstants::REJECTED_REASON];
                    }
                }
                catch (Exception\BadRequestException $e)
                {
                    $this->trace->traceException($e);
                }
            }
        }

        return $resp;
    }

    /**
     * @param $actionId
     * @param $input
     */
    public function needsMerchantClarificationOnWorkflow($actionId, $input)
    {
        $this->trace->info(TraceCode::WORKFLOW_NEEDS_MERCHANT_CLARIFICATION, [
            Entity::ID => $actionId,
        ]);

        (new Validator)->validateInput('need_clarification', $input);

        Entity::verifyIdAndStripSign($actionId);

        $workFlowAction = $this->repo->workflow_action->findOrFailPublic($actionId);

        (new Validator)->validateWorkflowActionForNeedMerchantClarification($workFlowAction);

        $commentEntity = $this->repo->transactionOnLiveAndTest(function() use ($workFlowAction, $input) {

            $workFlowAction->untag(WorkflowActionConstants::WORKFLOW_MERCHANT_RESPONDED_TAG);

            $workFlowAction->tag(WorkflowActionConstants::WORKFLOW_NEEDS_MERCHANT_CLARIFICATION_TAG);

            $comment = $this->core()->addNeedClarificationComment($workFlowAction, $input);

            $this->repo->workflow_action->saveOrFail($workFlowAction);

            return $comment;
        });

        $this->core()->trackSelfServeEventForNeedClarification($workFlowAction);

        $this->core()->getSelfServeActionForAnalyticsForNeedClarification($workFlowAction);

        $this->core()->notifyMerchantForNeedClarification($workFlowAction, $input);

        return [
            WorkflowActionConstants::ADDED_COMMENT => $commentEntity->toArrayPublic(),
            WorkflowActionConstants::ADDED_TAG     => str_replace(' ', '-', strtolower(WorkflowActionConstants::WORKFLOW_NEEDS_MERCHANT_CLARIFICATION_TAG)),
        ];
    }
}
