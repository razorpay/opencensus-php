<?php

namespace RZP\Models\Admin\Role;

use RZP\Models\Base;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Action;
use RZP\Models\Admin\Permission;

class Service extends Base\Service
{
    public function create($orgId, $input)
    {
        $org = $this->repo->org->findByPublicId($orgId);

        if (empty($input[Entity::PERMISSIONS]) === false)
        {
            Permission\Entity::verifyIdAndStripSignMultiple(
                $input[Entity::PERMISSIONS]);
        }

        $role = $this->core()->create($org, $input);

        return $role->toArrayPublic();
    }

    public function getRole($orgId, $roleId)
    {
        $role = $this->repo->role->findByPublicIdAndOrgIdWithRelations(
            $roleId, $orgId, ['permissions']);

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

        $role->getValidator()->validateRoleIsNotSuperAdmin();

        $role->setAuditAction(Action::DELETE_ROLE);

        $this->repo->deleteOrFail($role);

        return $role->toArrayDeleted();
    }

    public function putRole(string $orgId, string $roleId, array $input)
    {
        if (empty($input[Entity::PERMISSIONS]) === false)
        {
            Permission\Entity::verifyIdAndStripSignMultiple(
                $input[Entity::PERMISSIONS]);
        }

        $role = $this->repo->role->findByPublicIdAndOrgId($roleId, $orgId);

        $admin = $this->app['basicauth']->getAdmin();

        $role->getValidator()->validateRoleIsNotSuperAdmin($admin);

        $role = $this->core()->edit($role, $input);

        return $role->toArrayPublic();
    }
}
