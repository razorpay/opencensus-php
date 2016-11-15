<?php

namespace RZP\Models\Admin\Role;

use RZP\Models\Admin\Org\Entity as Org;
use RZP\Models\Base;

class Service extends Base\Service
{
    public function createRole($orgId, $input)
    {
        $role = (new Core)->create($orgId, $input);

        return $role->toArrayPublic();
    }

    public function getRole($orgId, $roleId)
    {
        $orgId = Org::verifyIdAndStripSign($orgId);

        $roleId = Entity::verifyIdAndStripSign($roleId);

        $role = $this->repo->role->fetchRoleForOrg($roleId, $orgId);

        return $role;
    }

    public function getMultipleRoles($orgId)
    {
        Org::verifyIdAndStripSign($orgId);

        $role = $this->repo->role->fetchRolesForOrg($orgId);

        return $role->toArrayPublic();
    }

    public function deleteRole($orgId, $id)
    {
        $orgId = Org::verifyIdAndStripSign($orgId);
        $id = Entity::verifyIdAndStripSign($id);

        $role = $this->repo->role->retrieveByOrgIdAndIdOrFail($orgId, $id);

        $this->repo->deleteOrFail($role);

        return ['success' => true];
    }
}
