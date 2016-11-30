<?php

namespace RZP\Models\Admin\Permission;

use RZP\Models\Admin\Org\Entity as Org;
use RZP\Models\Base;

class Service extends Base\Service
{
    public function createPermission(array $input)
    {
        $permission = (new Core)->create($input);

        return $permission->toArrayPublic();
    }

    public function getPermission(string $permissionId)
    {
        Entity::verifyIdAndStripSign($permissionId);

        $permission = $this->repo->permission->findOrFail($permissionId);

        return $permission->toArrayPublic();
    }

    public function getMultiplePermissions(array $input)
    {
        $permission = $this->repo->permission->fetchAll($input);

        return $permission->toArrayPublic();
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

    public function createPermissionsFromJson(array $input)
    {
        $permission = (new Core)->create($input);

        return $permission->toArrayPublic();
    }
}
