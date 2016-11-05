<?php

namespace RZP\Models\Admin\Role;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'role';

    public function fetchRoleForOrg($roleId, $orgId)
    {
        return $this->newQuery()
                    ->where(Entity::ID,'=',$roleId)
                    ->where(Entity::ORG_ID,'=',$orgId)
                    ->first();
    }

    public function fetchRolesForOrg($orgId)
    {
        return $this->newQuery()
                    ->where(Entity::ORG_ID,'=',$orgId)
                    ->get();
    }
}
