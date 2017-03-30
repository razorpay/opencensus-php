<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\Workflow\Base;
use RZP\Models\Workflow\Action\State;

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

    public function findByAdminIdAndOrgId($adminId, $orgId)
    {
        return $this->newQuery()
                    ->where(Entity::ADMIN_ID, '=', $adminId)
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->firstOrFailPublic();
    }

    public function findOpenWorkflows(string $workflowId)
    {
        return $this->newQuery()
                    ->where(Entity::WORKFLOW_ID, '=', $workflowId)
                    ->whereIn(Entity::STATE, [State\Entity::APPROVED, State\Entity::EXECUTED, State\Entity::OPEN])
                    ->get();
    }
}
