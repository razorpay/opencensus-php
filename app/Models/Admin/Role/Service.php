<?php

namespace RZP\Models\Admin\Role;

use RZP\Models\Admin\Org\Entity as Org;
use RZP\Models\Admin\Permission;
use RZP\Models\Base;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function createRole($orgId, $input)
    {
        $orgId = Org::verifyIdAndStripSign($orgId);

        if (isset($input['permissions']) === true)
        {
            $permIds = [];

            foreach ($input['permissions'] as $permId)
            {
                $permIds[] = Permission\Entity::verifyIdAndStripSign($permId);
            }

            $input['permissions'] = $permIds;
        }

        $role = $this->core->create($orgId, $input);

        return $role->toArrayPublic();
    }

    public function getRole($orgId, $roleId)
    {
        $orgId = Org::verifyIdAndStripSign($orgId);

        $roleId = Entity::verifyIdAndStripSign($roleId);

        $role = $this->repo->role->fetchRoleForOrg($roleId, $orgId);

        return $role->toArrayPublic();
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

    public function putRole(string $orgId, string $id, array $input)
    {
        $orgId = Org::verifyIdAndStripSign($orgId);
        $id = Entity::verifyIdAndStripSign($id);

        if (isset($input['permissions']) === true)
        {
            foreach ($input['permissions'] as $permId)
            {
                $permIds[] = Permission\Entity::verifyIdAndStripSign($permId);
            }

            $permIds = [];

            $input['permissions'] = $permIds;
        }

        $role = $this->core->edit($orgId, $id, $input);

        return $role;
    }
}
