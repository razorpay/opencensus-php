<?php

namespace RZP\Models\Admin\Permission;

use RZP\Models\Admin\Org\Entity as Org;
use RZP\Models\Base;

class Service extends Base\Service
{
    public function createPermission($orgId, array $input)
    {
        $permission = (new Core)->create($orgId, $input);

        return $permission->toArrayPublic();
    }

    public function getPermission($orgId, $permissionId)
    {
        Org::verifyIdAndStripSign($orgId);

        Entity::verifyIdAndStripSign($permissionId);

        $permission = $this->repo->permission->fetchPermissionForOrg($permissionId, $orgId);

        return $permission->toArrayPublic();
    }

    public function getMultiplePermissions($orgId)
    {
        $permission = $this->repo->permission->fetchPermissionsForOrg($orgId);

        return $permission->toArrayPublic();
    }

}
