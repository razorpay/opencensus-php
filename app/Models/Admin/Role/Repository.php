<?php

namespace RZP\Models\Admin\Role;

use RZP\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'role';

    protected $proxyFetchParamRules = [
        Entity::NAME    => 'sometimes|string',
    ];

    protected $appFetchParamRules = [
        Entity::NAME    => 'sometimes|string',
    ];

    public function fetchRoleForOrg($roleId, $orgId)
    {
        return $this->newQuery()
                    ->where(Entity::ID,'=',$roleId)
                    ->where(Entity::ORG_ID,'=',$orgId)
                    ->with('permissions')
                    ->first();
    }

    public function fetchRolesForOrg($orgId)
    {
        return $this->newQuery()
                    ->where(Entity::ORG_ID,'=',$orgId)
                    ->with('permissions')
                    ->get();
    }
}
