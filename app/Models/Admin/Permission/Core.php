<?php

namespace RZP\Models\Admin\Permission;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $permission = (new Entity)->build($input);

        $this->repo->saveOrFail($permission);

        return $permission;
    }

    public function getMultiplePermissionIdsByNames(array $names)
    {
        $permissions = $this->repo->permission->retrieveIdsByNames($names);

        $permIds = [];

        foreach($permissions as $perm)
        {
            $permIds[] = $perm->getPublicId();
        }

        return $permIds;
    }
}
