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

        $permission = $this->repo->permission->findorfailpublic($permissionId);

        return $permission->toArrayPublic();
    }

    public function deletePermission(string $permId)
    {
        Entity::verifyIdAndStripSign($permId);

        $perm = $this->repo->permission->findorfailpublic($permId);

        $data = $this->core()->delete($perm);

        return $data;
    }

    public function editPermission(string $permId, array $input)
    {
        Entity::verifyIdAndStripSign($permId);

        $perm = $this->repo->permission->findOrFail($permId);

        $perm = $this->core()->edit($perm, $input);

        return $perm->toArrayPublic();
    }

    public function getMultiplePermissions(array $input)
    {
        $perms = $this->repo->permission->fetch($input);

        return $perms->toArrayPublic();
    }
}
