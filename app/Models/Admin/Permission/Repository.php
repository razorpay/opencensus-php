<?php

namespace RZP\Models\Admin\Permission;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'permission';

    public function fetchPermissionForOrg($permissionId)
    {
        return $this->newQuery()
                    ->where(Entity::ID,'=',$permissionId)
                    ->first();
    }
}
