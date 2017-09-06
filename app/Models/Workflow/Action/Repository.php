<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\Workflow\Base;
use RZP\Models\Admin\Org;
use RZP\Models\Workflow\Action\State;
use RZP\Models\Workflow\Action\Checker;
use RZP\Constants\Table;

class Repository extends Base\Repository
{
    protected $entity = 'workflow_action';

    protected $adminFetchParamRules = [
        Entity::ADMIN_ID     => 'sometimes|string|max:14',
        Entity::WORKFLOW_ID  => 'sometimes|string|max:14',
        Entity::ORG_ID       => 'sometimes|string|max:14',
        self::EXPAND . '.*'  => 'string|in:admin,',
        Entity::TYPE         => 'sometimes|string|max:10',
        Entity::PERMISSION   => 'sometimes|boolean|in:0,1',
        State\Entity::CLOSED => 'sometimes|boolean|in:0,1',
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
            $openStates = State\Entity::OPEN_STATES;

            $query->whereIn(Entity::STATE, $openStates);
        }
    }

    public function addQueryOrder($query)
    {
        $query->orderBy(Entity::CREATED_AT, 'desc');
    }

    public function findByAdminIdAndOrgIdWithRelations(
        string $adminId,
        string $orgId,
        array $relations = [],
        int $skip = 0,
        int $count = 10)
    {
        return $this->getNewQueryWithPermissions()
                    ->where(Entity::ADMIN_ID, '=', $adminId)
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->with($relations)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->skip($skip)
                    ->take($count)
                    ->get();
    }

    public function findActionsForChecker(
        array $roleIds,
        array $relations = [],
        int $skip = 0,
        int $count = 10)
    {
        /*
            SELECT wa.id, wa.title, wa.description
            FROM workflow_actions wa
            JOIN
                workflow_steps ws ON wa.workflow_id = ws.workflow_id
                AND wa.current_level = ws.level
            JOIN
                permissions p ON p.id = wa.permission_id
            WHERE
                wa.state = 'open'
                AND ws.role_id IN ($adminIds);
        */

        $wStep = Table::WORKFLOW_STEP;

        return $this->getNewQueryWithPermissions()
                    ->join($wStep, function ($join) {
                        $join->on('workflow_actions.workflow_id', '=', 'workflow_steps.workflow_id')
                             ->on('workflow_actions.current_level', '=', 'workflow_steps.level');
                    })
                    ->where('workflow_actions.state', '=', State\Entity::OPEN)
                    ->whereIn('workflow_steps.role_id', $roleIds)
                    ->with($relations)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->skip($skip)
                    ->take($count)
                    ->get();
    }

    /**
     * Function to add closed state param to the query.
     * @param $query
     * @param $params
     */
    public function addQueryParamClosed($query, $params)
    {
        $adminId = $this->auth->getAdmin()->getId();

        $acsDao = $this->repo->action_state;

        $acsTable = $acsDao->getTableName();

        $aId = $this->dbColumn(Entity::ID);
        $acsActionId = $acsDao->dbColumn(State\Entity::ACTION_ID);

        $acsState = $acsDao->dbColumn(State\Entity::NAME);

        // CLOSED is the absolute last state, We can expect unique entries.
        $acsAdminId = $acsDao->dbColumn(State\Entity::ADMIN_ID);

        $query->join($acsTable, $aId, '=', $acsActionId)
              ->where($acsState, '=', State\Entity::CLOSED)
              ->where($acsAdminId, '=', $adminId);
    }

    public function fetchOpenActionsByWorkflowId(string $workflowId)
    {
        $openStates = State\Entity::OPEN_STATES;

        return $this->newQuery()
                    ->where(Entity::WORKFLOW_ID, '=', $workflowId)
                    ->whereIn(Entity::STATE, $openStates)
                    ->get();
    }

    public function getActionsCheckedByAdmin(
        string $adminId,
        array $relations = [],
        int $skip = 0,
        int $count = 10)
    {
        $checkerRepo = $this->repo->action_checker;

        $attributes = $this->dbColumn('*');
        $aId = $this->repo->workflow_action->dbColumn(Entity::ID);

        $cActionId = $checkerRepo->dbColumn(Checker\Entity::ACTION_ID);

        $cAdminId = $checkerRepo->dbColumn(Checker\Entity::ADMIN_ID);

        $checkerTable = Table::ACTION_CHECKER;

        return $this->newQuery()
                    ->select($attributes)
                    ->join($checkerTable, $aId, '=', $cActionId)
                    ->where($cAdminId, '=', $adminId)
                    ->with($relations)
                    ->take($count)
                    ->skip($skip)
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
                    ->whereIn(Entity::STATE, State\Entity::OPEN_STATES)
                    ->get();
    }
}
