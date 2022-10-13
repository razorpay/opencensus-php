<?php

namespace RZP\Models\Admin\Role;

use RZP\Models\Admin\Org;
use RZP\Models\Admin\Permission;
use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\Admin\Action;

class Core extends Base\Core
{
    public function create(Org\Entity $org, array $input)
    {
        $role = (new Entity)->generateId();

        $role->setAuditAction(Action::CREATE_ROLE);

        $role->org()->associate($org);

        $role->build($input);

        $this->validateExistingRole($role);

        $this->repo->saveOrFail($role);

        $this->syncPermissions($role, $input);

        $role = $this->repo->role->findOrFailPublicWithRelations(
            $role->getId(), [Entity::PERMISSIONS]);

        return $role;
    }

    public function edit(Entity $role, array $input)
    {
        $role->edit($input);

        $role->setAuditAction(Action::EDIT_ROLE);

        $this->repo->saveOrFail($role);

        $this->syncPermissions($role, $input);

        $role = $this->repo->role->findOrFailPublicWithRelations(
            $role->getId(), [Entity::PERMISSIONS]);

        return $role;
    }

    public function validateRoles(array $roles)
    {
        if (isset($roles) === true)
        {
            Entity::verifyIdAndStripSignMultiple($roles);
            $this->repo->role->validateExists($roles);
        }
    }

    public function validatePermissions(array $permissions)
    {
        if (isset($permissions) === true)
        {
            $this->repo->permission->validateExists($permissions);
        }
    }

    public function addPermissionsToRoles($role, $permissions)
    {
        try
        {
            $this->repo->sync($role, Entity::PERMISSIONS, $permissions, false);
        }
        catch (\Exception $e)
        {
            return false;
        }

        return true;
    }

    protected function syncPermissions($role, array $input)
    {
        if (isset($input[Entity::PERMISSIONS]) === true)
        {
            $this->repo->permission->validateExists($input[Entity::PERMISSIONS]);

            $this->repo->sync(
                $role, Entity::PERMISSIONS, $input[Entity::PERMISSIONS]);
        }
    }

    protected function validateExistingRole(Entity $role)
    {
        $params = [
            Entity::ORG_ID => $role->getOrgId(),
            Entity::NAME   => $role->getName()
        ];

        $roles = $this->repo->role->fetch($params);

        if ($roles->count() !== 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The role with the name already exists');
        }
    }

    public function findRoleByOrgAndName(Org\Entity $org, string $name)
    {
        return $this->repo->role->findByOrgAndName($org, $name);
    }

    public function findRoleByOrgIdAndName(string $orgId, string $name)
    {
        return $this->repo->role->findByOrgIdAndName($orgId, $name);
    }

    public function getRolesForPermissionName(string $name, string $orgId)
    {
        return $this->repo->role->getRolesForPermissionName($name, $orgId);
    }
}
