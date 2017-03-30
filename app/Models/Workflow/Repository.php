<?php

namespace RZP\Models\Workflow;

use RZP\Constants\Table;
use RZP\Models\Workflow\Step;

class Repository extends Base\Repository
{
    protected $entity = 'workflow';

    protected $adminFetchParamRules = [
        Entity::ORG_ID        => 'sometimes|string|max:14',
    ];

    public function fetchWorkflowsWithStepsByPermissions(array $permissionIds)
    {
        return $this->newQuery()
                    ->join(Table::WORKFLOW_PERMISSION, Entity::ID, '=', 'workflow_permissions.workflow_id')
                    ->with([Entity::STEPS, Entity::PERMISSIONS])
                    ->whereIn('workflow_permissions.permission_id', $permissionIds)
                    ->get();
    }
}
