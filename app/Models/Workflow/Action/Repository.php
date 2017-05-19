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
        Entity::ADMIN_ID    => 'sometimes|string|max:14',
        Entity::WORKFLOW_ID => 'sometimes|string|max:14',
        Entity::ORG_ID      => 'sometimes|string|max:14',
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

    public function findByOrgId(string $orgId)
    {
        return $this->getNewQueryWithPermissions()
                    ->orgId($orgId)
                    ->get();
    }

    public function findByAdminIdAndOrgIdWithRelations($adminId, $orgId, $relations = [])
    {
        $permission = Table::PERMISSION;

        return $this->getNewQueryWithPermissions()
                    ->where(Entity::ADMIN_ID, '=', $adminId)
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->with($relations)
                    ->get();
    }

    public function findByAdminIdAndOrgId($adminId, $orgId, $relations = [])
    {
        return $this->newQuery()
                    ->where(Entity::ADMIN_ID, '=', $adminId)
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->with($relations)
                    ->firstOrFailPublic();
    }

    public function findActionsForChecker(array $roleIds)
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

        $permission = Table::PERMISSION;

        return $this->getNewQueryWithPermissions()
                    ->join($wStep, function ($join) {
                        $join->on('workflow_actions.workflow_id', '=', 'workflow_steps.workflow_id')
                             ->on('workflow_actions.current_level', '=', 'workflow_steps.level');
                    })
                    ->where('workflow_actions.state', '=', State\Entity::OPEN)
                    ->whereIn('workflow_steps.role_id', $roleIds)
                    ->get();
    }

    public function getClosedActionsByAdmin($adminId)
    {
        /*
         * SELECT `workflow_actions`.*
         * FROM `workflow_actions` wa
         * JOIN `action_states` acs ON acs.action_id = wa.id
         *      AND `actions_states`.name = 'closed';
         *
         */

        $acsDao = $this->repo->action_state;

        $acsTable = $acsDao->getTableName();

        $aId = $this->dbColumn(Entity::ID);
        $acsActionId = $acsDao->dbColumn(State\Entity::ACTION_ID);

        $acsState = $acsDao->dbColumn(State\Entity::NAME);

        // CLOSED is the absolute last state, We can expect unique entries.
        $acsAdminId = $acsDao->dbColumn(State\Entity::ADMIN_ID);


        return $this->getNewQueryWithPermissions()
                    ->join($acsTable, $aId, '=', $acsActionId)
                    ->where($acsState, '=', State\Entity::CLOSED)
                    ->where($acsAdminId, '=', $adminId)
                    ->get();
    }

    public function findOpenActionsByOrgId(string $orgId)
    {
        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $openStates = State\Entity::OPEN_STATES;

        return $this->getNewQueryWithPermissions()
                    ->orgId($orgId)
                    ->whereIn(Entity::STATE, $openStates)
                    ->get();
    }

    public function fetchOpenActionsByWorkflowId(string $workflowId)
    {
        $openStates = State\Entity::OPEN_STATES;

        return $this->newQuery()
                    ->where(Entity::WORKFLOW_ID, '=', $workflowId)
                    ->whereIn(Entity::STATE, $openStates)
                    ->get();
    }

    public function getActionsCheckedByAdmin(string $adminId)
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
                    ->get();
    }
}
