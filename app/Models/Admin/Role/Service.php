<?php

namespace RZP\Models\Admin\Role;

use RZP\Models\Base;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Permission;

class Service extends Base\Service
{
    public function create($orgId, $input)
    {
        $org = $this->repo->org->findByPublicId($orgId);

        $role = $this->core()->create($org, $input);

        return $role->toArrayPublic();
    }

    public function getRole($orgId, $roleId)
    {
        $role = $this->repo->role->findByPublicIdAndOrgIdWithRelations($roleId, $orgId);

        return $role->toArrayPublic();
    }

    public function getMultipleRoles($orgId)
    {
        Org\Entity::verifyIdAndStripSign($orgId);

        $role = $this->repo->role->fetchRolesForOrg($orgId);

        return $role->toArrayPublic();
    }

    public function deleteRole($orgId, $roleId)
    {
        $role = $this->repo->role->findByPublicIdAndOrgId($roleId, $orgId);

        $this->repo->deleteOrFail($role);

        // @todo: To maintain bc. Remove first two lines later.
        $ret = $role->toArrayDeleted();
        $ret = array_merge($ret, ['success' => true]);
        return $ret;
    }

    public function putRole(string $orgId, string $roleId, array $input)
    {
        $role = $this->repo->role->findByPublicIdAndOrgId($roleId, $orgId);

        $this->core()->edit($role, $input);

        return $role->toArrayPublic();
    }
}
