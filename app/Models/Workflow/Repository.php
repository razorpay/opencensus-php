<?php

namespace RZP\Models\Workflow;

use Constants\Table;
use RZP\Models\Workflow\Step;

class Repository extends Base\Repository
{
    protected $entity = 'workflow';

    protected $adminFetchParamRules = [
        Entity::ORG_ID        => 'sometimes|string|max:14',
    ];

    public function fetchWorkflowsWithStepsByPermissions(array $permissionIds)
    {
        $allWorkflowColumns = $this->getAttributeWithTableName("*");

        $permissionWorkflowId = $this->manager
                                     ->workflow_permissions
                                     ->getAttributeWithTableName(Step\Entity::WORKFLOW_ID);

        $permissionId = $this->manager
                             ->workflow_permissions
                             ->getAttributeWithTableName(Entity::PERMISSION_ID);

        return $this->newQuery()
                    ->join(Table::WORKFLOW_PERMISSION, Entity::ID, "=", $permissionWorkflowId)
                    ->with('steps', 'permissions')
                    ->whereIn($permissionId, $permissionIds)
                    ->get();
    }
}
