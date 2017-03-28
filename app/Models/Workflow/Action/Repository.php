<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\Workflow\Base;

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
}
