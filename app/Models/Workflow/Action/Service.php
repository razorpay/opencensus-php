<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Service extends Base\Service
{
    protected $admin;

    const ACTION_FUNCTION_MAPPING = [
        "maker" => [
            "all"     => "getActionsByOrg",
            "closed"  => "getClosedActionsByMaker",
            "open"    => "getActionsByOrg",
            "maker"   => "getActionsByMaker",
        ],
        "checker" => [
            "all"     => "getActionsForChecker",
        ],
        "admin_checked" => [
            "all"     => "getActionsCheckedByAdmin",
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

    public function get(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $action = $this->core()->get($id);

        return $action->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $orgId = $this->admin->getOrgId();

        // $duty can be maker/checker/admin_checked
        // actions will be fetched based on duty and type
        // type can be all/closed/open etc

        $duty = $input['duty'] ?? 'default';
        $type = $input['type'] ?? 'all';

        if (isset(self::ACTION_FUNCTION_MAPPING[$duty][$type]))
        {
            // Function name which needs to be called to return actions based on duty and maker.
            $actionFunctionName = self::ACTION_FUNCTION_MAPPING[$duty][$type];

            $actions = call_user_func_array([$this, $actionFunctionName], [$input]);
        }
        else
        {
            $actions = $this->repo->workflow_action->findByOrgId($orgId);
        }

        return $actions->toArrayPublic();
    }

    public function getActionsCheckedByAdmin($input)
    {
        $actions = $this->repo->workflow_action
            ->getActionsCheckedByAdmin(
                $this->admin->getId(), ['admin']);

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

    public function getStatesOfAction(string $actionId)
    {
        Entity::verifyIdAndStripSign($actionId);

        $states = $this->repo
                       ->action_state
                       ->fetchStateTransitionsByActionId($actionId)
                       ->map(function ($state) {
                        return $state->toArrayPublic();
                       })
                       ->toArray();

        return $states;
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
        $adminRoleIds = $this->admin->roles()->allRelatedIds()->toArray();

        $actions = $this->repo->workflow_action->findActionsForChecker(
            $adminRoleIds, ['admin']);

        return $actions;
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function getActionsByOrg(array $input)
    {
        $orgId = $this->admin->getOrgId();

        $type = $input['type'] ?? 'all';

        $this->app['basicauth']->validateSuperAdminAccess();

        $actions = $this->repo->workflow_action->findByOrgId(
            $orgId, ['admin'], $type);

        return $actions;
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    public function getClosedActionsByMaker(array $input)
    {
        $actions = $this->repo->workflow_action
            ->getClosedActionsByAdmin(
                $this->admin->getId(), ['admin']);

        return $actions;
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    public function getActionsByMaker(array $input)
    {
        $relations = ['workflow', 'admin'];

        $actions = $this->repo->workflow_action->findByAdminIdAndOrgIdWithRelations(
            $this->admin->getId(), $this->admin->getOrgId(), $relations);

        return $actions;
    }
}
