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

        return $permissions;
    }

    public function edit(Entity $perm, array $input)
    {
        $perm->edit($input);

        $this->repo->saveOrFail($perm);

        return $perm;
    }

    public function delete(Entity $perm)
    {
        $this->repo->deleteOrFail($perm);

        $ret = $perm->toArrayDeleted();

        $ret = array_merge($ret, ['success' => true]);

        return $ret;
    }
}
