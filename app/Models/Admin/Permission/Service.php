<?php

namespace RZP\Models\Admin\Permission;

use RZP\Models\Base;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Role;

class Service extends Base\Service
{
    public function createPermission(array $input)
    {
        if (empty($input[Entity::ORGS]) === false)
        {
            Org\Entity::verifyIdAndStripSignMultiple($input[Entity::ORGS]);
        }

        // Orgs for which workflows have to be enabled
        if (empty($input[Entity::WORKFLOW_ORGS]) === false)
        {
            Org\Entity::verifyIdAndStripSignMultiple(
                $input[Entity::WORKFLOW_ORGS]);
        }

        $permission = $this->core()->create($input);

        $response = $permission->toArrayPublic();

        return $response;
    }

    public function getPermission(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $relations = ['orgs', 'workflow_orgs'];

        $permission = $this->core()->get($id, $relations);

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

        if (empty($input[Entity::ORGS]) === false)
        {
            Org\Entity::verifyIdAndStripSignMultiple($input[Entity::ORGS]);
        }

        // Orgs for which workflows have to be enabled
        if (empty($input[Entity::WORKFLOW_ORGS]) === false)
        {
            Org\Entity::verifyIdAndStripSignMultiple(
                $input[Entity::WORKFLOW_ORGS]);
        }

        $permission = $this->repo->permission->findOrFail($id);

        $permission = $this->core()->edit($permission, $input);

        return $permission->toArrayPublic();
    }

    public function getMultiplePermissions(string $orgId, array $input)
    {
        $type = $input['type'] ?? null;

        $perms = $this->repo->permission->fetchAllByOrg($orgId, $type);

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

        $orgId = $this->app['basicauth']->getAdminOrgId();

        $roles = $this->repo->role->getRolesForPermission($id, $orgId);

        return $roles->toArrayPublic();
    }
}
