<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Service extends Base\Service
{
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
        $admin = $this->app['basicauth']->getAdmin();

        $orgId = $admin->getOrgId();

        $duty = $input['duty'] ?? 'org';

        switch ($duty)
        {
            case 'maker':
                $actions = $this->getActionsByMakerAndType($admin, $input);
                break;

            case 'checker':
                $actions = $this->getActionsForChecker($admin);
                break;

            case 'admin_checked':
                $actions = $this->getActionsCheckedByAdmin($admin);
                break;

            case 'org':
            default:
                $actions = $this->repo->workflow_action->findByOrgId($orgId);
                break;
        }

        return $actions->toArrayPublic();
    }

    public function getActionsCheckedByAdmin($admin)
    {
        $actions = $this->repo->workflow_action
            ->getActionsCheckedByAdmin(
                $admin->getId(), ['admin']);

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
     * @return array
     */
    public function getActionsForChecker($admin)
    {
        $adminRoleIds = $admin->roles()->allRelatedIds()->toArray();

        $actions = $this->repo->workflow_action->findActionsForChecker(
            $adminRoleIds, ['admin']);

        return $actions;
    }

    public function getActionsByMakerAndType($admin, array $input)
    {
        $orgId = $admin->getOrgId();

        $type = $input['type'] ?? 'maker';

        switch ($type)
        {
            case 'all':
                $actions = $this->getActionsByOrg($orgId, $type);
                break;

            case 'closed':
                $actions = $this->getClosedActionsByMaker($admin);
                break;

            case 'open':
                $actions = $this->getActionsByOrg($orgId, $type);
                break;

            case 'maker':
            default:
                $actions = $this->getActionsByMaker($admin);
                break;
        }

        return $actions;
    }

    /**
     * @param string $orgId
     * @param string $type  - all/open
     * @return array
     */
    public function getActionsByOrg(string $orgId, $type)
    {
        $this->app['basicauth']->validateSuperAdminAccess();

        $actions = $this->repo->workflow_action->findByOrgId(
            $orgId, ['admin'], $type);

        return $actions;
    }

    public function getClosedActionsByMaker($admin)
    {
        $actions = $this->repo->workflow_action
            ->getClosedActionsByAdmin(
                $admin->getId(), ['admin']);

        return $actions;
    }

    public function getActionsByMaker($admin)
    {
        $relations = ['workflow', 'admin'];

        $actions = $this->repo->workflow_action->findByAdminIdAndOrgIdWithRelations(
            $admin->getId(), $admin->getOrgId(), $relations);

        return $actions;
    }
}
