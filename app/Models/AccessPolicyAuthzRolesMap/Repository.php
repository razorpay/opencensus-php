<?php

namespace  RZP\Models\AccessPolicyAuthzRolesMap;

use RZP\Models\Base;
use RZP\Constants;

class Repository extends Base\Repository
{
    protected $entity = Constants\Table::ACCESS_POLICY_AUTHZ_ROLES_MAP;

    public function findByPrivilegeIdAndAction($privilegeId, $action)
    {
        $query =  $this->newQuery()
            ->where(Entity::PRIVILEGE_ID, '=', $privilegeId)
            ->where(Entity::ACTION, '=', $action);

        return $query->first();
    }

    public function deleteAll()
    {
        $this->newQuery()->truncate();
    }

}
