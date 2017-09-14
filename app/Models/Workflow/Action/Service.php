<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Exception\InvalidArgumentException;
use RZP\Models\Workflow\Constants;

class Service extends Base\Service
{
    protected $admin;

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

        $this->admin = $this->app['basicauth']->getAdmin();
    }

    public function create(array $input)
    {
        $action = $this->core()->create($input);

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
        $input[Constants::EXPAND] = ['admin'];

        $input[Constants::ACTIONS_CHECKED] = true;

        $actions = $this->repo->workflow_action->fetch($input);

        return $actions;
    }

    public function getActionDetails(string $actionId)
    {
        $admin = $this->app['basicauth']->getAdmin();

        $orgId = $admin->getOrgId();

        Entity::verifyIdAndStripSign($actionId);

        $relations = ['workflow.steps', 'admin', 'permission'];

        $action = $this->repo
                       ->workflow_action
                       ->findByIdAndOrgId($actionId, $orgId, $relations)
                       ->first();

        $data = $action->toArrayPublicWithAdminAndSteps();

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
                         ->action_comment
                         ->fetchByActionIdWithRelations(
                             $actionId, [Entity::ADMIN]);

        $data['comments'] = $comments->map(function ($comment)
        {
            return $comment->toArrayPublic();
        });

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

        $admin = $this->app['basicauth']->getAdmin();

        $action = $this->repo->workflow_action->findOrFailPublic($id);

        $this->core()->close($action, $admin);

        // fetch again from db to get updated values
        $action = $this->repo->workflow_action->findOrFailPublic($id);

        return $action->toArrayPublic();
    }

    public function executeAction(string $id)
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

        return $this->core()->executeAction($action);
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
        $input[Constants::EXPAND] = ['admin'];

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
        // Only superadmin can access maker.all and maker.open
        $this->app['basicauth']->validateSuperAdminAccess();

        $input[Entity::ORG_ID] = $this->admin->getOrgId();

        $input[Constants::EXPAND] = ['admin'];

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

        $input[Constants::EXPAND] = ['admin'];

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

        $input[Constants::EXPAND] = ['workflow', 'admin'];

        $input[Entity::ORG_ID] = $this->admin->getOrgId();

        $input[Entity::ADMIN_ID] = $this->admin->getId();

        $actions = $this->repo->workflow_action->fetch($input);

        return $actions;
    }
}
