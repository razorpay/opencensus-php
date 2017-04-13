<?php

namespace RZP\Models\Admin\Permission;

use RZP\Models\Base;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Role;

class Service extends Base\Service
{
    public function createPermission(array $input)
    {
        $permission = $this->repo->transactionOnLiveAndTest(function() use($input){

            $permission = $this->core()->create($input);

            if (empty($input[Entity::ORGS]) === false)
            {
                Org\Entity::verifyIdAndStripSignMultiple($input[Entity::ORGS]);

                $this->repo->sync($permission, 'orgs', $input[Entity::ORGS]);
            }

            return $permission;
        });

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
        Org\Entity::verifyIdAndStripSign($orgId);

        $perms = $this->repo->permission->fetchAllByOrg($orgId);

        return $perms->toArrayPublic();
    }

    public function getAssignablePermissions()
    {
        $perms = $this->repo->permission->fetchAllAssignable();

        return $perms->toArrayPublic();
    }

    public function getAllPermissions()
    {
        $perms = $this->repo->permission->fetchAll();

        return $perms->toArrayPublic();
    }

    public function getRolesForPermission(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $roles = $this->repo->permission->getRolesForPermission($id);

        Role\Entity::getSignedIdMultiple($roles);

        return $roles;
    }
}
