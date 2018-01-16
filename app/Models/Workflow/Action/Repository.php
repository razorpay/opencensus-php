<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\State;
use RZP\Constants\Table;
use RZP\Models\Admin\Org;
use RZP\Models\Workflow\Base;
use RZP\Models\Workflow\Constants;
use RZP\Models\Workflow\Action\Checker;

class Repository extends Base\Repository
{
    protected $entity = 'workflow_action';

    protected $adminFetchParamRules = [
        Entity::ADMIN_ID            => 'sometimes|string|max:14',
        Entity::WORKFLOW_ID         => 'sometimes|string|max:14',
        Entity::ORG_ID              => 'sometimes|string|max:14',
        self::EXPAND . '.*'         => 'string|in:admin,workflow,',
        Constants::TYPE             => 'sometimes|string|max:10',
        Entity::PERMISSION          => 'sometimes|boolean|in:0,1',
        Constants::CLOSED_ACTIONS   => 'sometimes|boolean|in:0,1',
        Constants::CHECKER_ACTIONS  => 'sometimes|boolean|in:0,1',
        Constants::ACTIONS_CHECKED  => 'sometimes|boolean|in:0,1',
    ];

    protected function getNewQueryWithPermissions()
    {
        $permission = Table::PERMISSION;

        return $this->newQuery()
                    ->select(
                        Table::WORKFLOW_ACTION . '.*',
                        'permissions.name AS permission_name',
                        'permissions.description AS permission_description')
                    ->join($permission, function ($join) {
                        $join->on('permissions.id', '=', 'workflow_actions.permission_id');
                    });
    }

    public function addQueryParamPermission($query, $params)
    {
        $permission = Table::PERMISSION;

        $query->select(
                    Table::WORKFLOW_ACTION . '.*',
                    'permissions.name AS permission_name',
                    'permissions.description AS permission_description')
                ->join($permission, function ($join) {
                    $join->on('permissions.id', '=', 'workflow_actions.permission_id');
                });
    }

    public function addQueryParamOrgId($query, $params)
    {
        $orgId = $params['org_id'];

        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $query->OrgId($orgId);
    }

    public function addQueryParamType($query, $params)
    {
        if ($params['type'] === 'open')
        {
            $openStates = State\Name::OPEN_ACTION_STATES;

            $query->whereIn(Entity::STATE, $openStates);
        }
    }

    public function addQueryOrder($query)
    {
        $query->orderBy(Entity::CREATED_AT, 'desc');
    }

    /**
     * Function to add closed state of the action to the query.
     * @param $query
     * @param $params
     */
    public function addQueryParamClosedActions($query, $params)
    {
        $adminId = $this->auth->getAdmin()->getId();

        $acsDao = $this->repo->state;

        $acsTable = $acsDao->getTableName();

        $aId = $this->dbColumn(Entity::ID);
        $acsActionId = $acsDao->dbColumn(State\Entity::ACTION_ID);

        $acsState = $acsDao->dbColumn(State\Entity::NAME);

        // CLOSED is the absolute last state, We can expect unique entries.
        $acsAdminId = $acsDao->dbColumn(State\Entity::ADMIN_ID);

        $query->join($acsTable, $aId, '=', $acsActionId)
              ->where($acsState, '=', State\Name::CLOSED)
              ->where($acsAdminId, '=', $adminId);
    }

    public function fetchOpenActionsByWorkflowId(string $workflowId)
    {
        $openStates = State\Name::OPEN_ACTION_STATES;

        return $this->newQuery()
                    ->where(Entity::WORKFLOW_ID, '=', $workflowId)
                    ->whereIn(Entity::STATE, $openStates)
                    ->get();
    }

    public function getOpenActionOnEntityOperation(
        string $entityId,
        string $entityName,
        string $permissionId)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::ENTITY_NAME, $entityName)
                    ->where(Entity::PERMISSION_ID, $permissionId)
                    ->whereIn(Entity::STATE, State\Name::OPEN_ACTION_STATES)
                    ->get();
    }

    /**
     * Will filter our checker actions which needs to be checked.
     * This will provide awaiting for your approval actions.
     * @param $query
     * @param $params
     */
    public function addQueryParamCheckerActions($query, $params)
    {
        $wStep = Table::WORKFLOW_STEP;

        $adminRoleIds = $this->auth->getAdmin()->roles()->allRelatedIds()->toArray();

        $query->join($wStep, function ($join) {
                    $join->on('workflow_actions.workflow_id', '=', 'workflow_steps.workflow_id')
                         ->on('workflow_actions.current_level', '=', 'workflow_steps.level');
                })
              ->where('workflow_actions.state', '=', State\Name::OPEN)
              ->whereIn('workflow_steps.role_id', $adminRoleIds);
    }

    public function addQueryParamActionsChecked($query, $params)
    {
        $adminId = $this->auth->getAdmin()->getId();

        $checkerRepo = $this->repo->action_checker;

        $attributes = $this->dbColumn('*');
        $aId = $this->repo->workflow_action->dbColumn(Entity::ID);

        $cActionId = $checkerRepo->dbColumn(Checker\Entity::ACTION_ID);

        $cAdminId = $checkerRepo->dbColumn(Checker\Entity::ADMIN_ID);

        $checkerTable = Table::ACTION_CHECKER;

        $query->select($attributes)
              ->join($checkerTable, $aId, '=', $cActionId)
              ->where($cAdminId, '=', $adminId);
    }

    /**
     * Get action entity with its relations like workflow,
     * workflow.steps, workflow.steps.role, admin, permission, etc.
     *
     * Important to note about workflow.steps relation is that
     * we may have previously executed workflow actions for which the
     * steps may have been soft deleted due to workflow steps change/edit.
     *
     * In such cases we select steps with action.created_at lying between
     * step.created_at and step.deleted_at.
     * 
     * For actions that are open or executed but the workflow steps haven't
     * been modified (and hence soft deleted) since the action was created
     * we just select the rows with step.deleted_at IS NULL and obviously
     * step.created_at >= action.created_at.
     */
    public function getActionDetails(string $id, string $orgId)
    {
        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $action = $this->newQuery()
                       ->orgId($orgId)
                       ->where(Entity::ID, '=', $id);

        $actionEntity = $action->first();

        // If no entity is returned then return
        // the query builder object which will be handled
        // aptly in the service
        if (empty($actionEntity) === true)
        {
            return $action;
        }

        $relations = [
            'workflow.steps' => function ($query) use ($actionEntity)
            {
                // Get all steps where action.created_at is between
                // step.created_at AND step.deleted_at (deleted/old steps) or it is more
                // than step.created_at but step.deleted_at is NULL (active steps)
                
                $query->withTrashed()
                      ->where(Entity::CREATED_AT, '<=', $actionEntity->getCreatedAt())
                      ->where(function ($query) use ($actionEntity)
                        {
                            $query->where(Entity::DELETED_AT, '>=', $actionEntity->getCreatedAt())
                                  ->orWhereNull(Entity::DELETED_AT);
                        });
            },
            'workflow.steps.role',
            'admin' => function ($query)
            {
                $query->withTrashed();
            },
            'permission'
        ];

        return $action->with($relations)
                      ->get();
    }

}
