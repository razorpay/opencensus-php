<?php

namespace RZP\Models\Admin\Permission;

use RZP\Models\Admin\Org\Entity as Org;
use RZP\Models\Base;

class Service extends Base\Service
{
    public function createPermission(array $input)
    {
        $permission = $this->core()->create($input);

        return $permission->toArrayPublic();
    }

    public function getPermission(string $permissionId)
    {
        Entity::verifyIdAndStripSign($permissionId);

        $permission = $this->repo->permission->findOrFailPublic($permissionId);

        return $permission->toArrayPublic();
    }

    public function deletePermission(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $permission = $this->repo->permission->findOrFailPublic($id);

        $permission = $this->core()->delete($permission);

        return $permission->toArrayDeleted();
    }

    public function editPermission(string $id, array $input)
    {
        Entity::verifyIdAndStripSign($id);

        $permission = $this->repo->permission->findOrFail($id);

        $permission = $this->core()->edit($permission, $input);

        return $permission->toArrayPublic();
    }

    public function getMultiplePermissions(string $orgId)
    {
        Org::verifyIdAndStripSign($orgId);

        $perms = $this->repo->permission->fetchAll($orgId);

        return $perms->toArrayPublic();
    }

    public function getAssignablePermissions()
    {
        $perms = $this->repo->permission->fetchAllAssignable();

        return $perms->toArrayPublic();
    }
}
