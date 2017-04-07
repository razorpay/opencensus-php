<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\Workflow\Base;
use RZP\Models\Workflow\Action\State;
use RZP\Constants\Table;

class Repository extends Base\Repository
{
    protected $entity = 'workflow_action';

    protected $adminFetchParamRules = [
        Entity::ADMIN_ID    => 'sometimes|string|max:14',
        Entity::WORKFLOW_ID => 'sometimes|string|max:14',
        Entity::ORG_ID      => 'sometimes|string|max:14',
    ];

    public function findByOrgId(string $orgId)
    {
        return $this->newQuery()
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->get();
    }

    public function findByAdminIdAndOrgIdWithRelations($adminId, $orgId, $relations = [])
    {
        return $this->newQuery()
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

    public function findOpenWorkflows(string $workflowId)
    {
        /*
            SELECT *
            FROM workflow_actions
            WHERE workflow_id = $workflow_id AND state IN ('open')
        */

        return $this->newQuery()
                    ->where(Entity::WORKFLOW_ID, '=', $workflowId)
                    ->whereIn(Entity::STATE, [State\Entity::OPEN])
                    ->get();
    }

    public function findActionsForChecker(array $roleIds)
    {
        /*
            SELECT wa.id, wa.title, wa.description
            FROM workflow_actions wa
            JOIN
                workflow_steps ws ON wa.workflow_id = ws.workflow_id
                AND wa.current_level = ws.level
            WHERE
                wa.state = 'open'
                AND ws.role_id IN ($adminIds);
        */

        $wStep = Table::WORKFLOW_STEP;

        return $this->newQuery()
                    ->select(Table::WORKFLOW_ACTION . '.*')
                    ->join($wStep, function ($join) {
                        $join->on('workflow_actions.workflow_id', '=', 'workflow_steps.workflow_id')
                             ->on('workflow_actions.current_level', '=', 'workflow_steps.level');
                    })
                    ->where('workflow_actions.state', '=', State\Entity::OPEN)
                    ->whereIn('workflow_steps.role_id', $roleIds)
                    ->get();
    }
}
