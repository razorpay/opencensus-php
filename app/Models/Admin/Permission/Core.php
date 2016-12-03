<?php

namespace RZP\Models\Admin\Permission;

use RZP\Models\Base;
use RZP\Models\Admin\Action;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $permission = (new Entity)->build($input);

        $permission->setAuditAction(Action::CREATE_PERMISSION);

        $this->repo->saveOrFail($permission);

        return $permission;
    }

    public function getMultiplePermissionIdsByNames(array $names)
    {
        $permissions = $this->repo->permission->retrieveIdsByNames($names);

        return $permissions;
    }
}
