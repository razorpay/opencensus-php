<?php

namespace RZP\Models\Admin\Role;

use RZP\Models\Base;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Action;
use RZP\Models\Admin\Permission;

class Service extends Base\Service
{
    public function create($input)
    {
        $orgId = $this->app['basicauth']->getAdmin()->getPublicOrgId();

        $org = $this->repo->org->findbyPublicId($orgId);

        if (empty($input[Entity::PERMISSIONS]) === false)
        {
            Permission\Entity::verifyIdAndStripSignMultiple(
                $input[Entity::PERMISSIONS]);
        }

        $role = $this->core()->create($org, $input);

        return $role->toArrayPublic();
    }

    public function getRole($roleId)
    {
        $orgId = $this->app['basicauth']->getAdmin()->getPublicOrgId();

        $role = $this->repo->role->findByPublicIdAndOrgIdWithRelations(
            $roleId, $orgId, ['permissions']);

        return $role->toArrayPublic();
    }

    public function listAdminsByRole($roleName)
    {
        $role = $this->core()->findRoleByOrgIdAndName(Org\Entity::RAZORPAY_ORG_ID, $roleName);

        $admins = $role->admins()->get();

        $data = $role->toArrayPublic();

        $data['admins'] = array_map(function($admin) {
            return [
                'id'         => $admin['id'],
                'name'       => $admin['name'],
                'email'      => $admin['email'],
                'disabled'   => $admin['disabled'],
                'deleted_at' => $admin['deleted_at']
            ];
        }, $admins->all());

        return $data;
    }


    public function getMultipleRoles()
    {
        $orgId = $this->app['basicauth']->getAdminOrgId();

        $role = $this->repo->role->fetchRolesForOrg($orgId);

        return $role->toArrayPublic();
    }

    public function deleteRole($roleId)
    {
        $orgId = $this->app['basicauth']->getAdmin()->getPublicOrgId();

        $role = $this->repo->role->findByPublicIdAndOrgId($roleId, $orgId);

        $role->getValidator()->validateRoleIsNotSuperAdmin();

        $role->setAuditAction(Action::DELETE_ROLE);

        $this->repo->deleteOrFail($role);

        return $role->toArrayDeleted();
    }

    public function putRole(string $roleId, array $input)
    {
        if (empty($input[Entity::PERMISSIONS]) === false)
        {
            Permission\Entity::verifyIdAndStripSignMultiple(
                $input[Entity::PERMISSIONS]);
        }

        $orgId = $this->app['basicauth']->getAdmin()->getPublicOrgId();

        $role = $this->repo->role->findByPublicIdAndOrgId($roleId, $orgId);

        $admin = $this->app['basicauth']->getAdmin();

        $role->getValidator()->validateRoleIsNotSuperAdmin($admin);

        $role = $this->core()->edit($role, $input);

        return $role->toArrayPublic();
    }

    public function putPermissionsToRoles(array $input)
    {
        if (empty($input[Entity::PERMISSIONS]) === false)
        {
            Permission\Entity::verifyIdAndStripSignMultiple($input[Entity::PERMISSIONS]);
        }

        $orgId = $this->app['basicauth']->getAdmin()->getPublicOrgId();

        $roles = $input[Entity::ROLES];
        $permissions = $input[Entity::PERMISSIONS];

        $this->core()->validateRoles($roles);
        $this->core()->validatePermissions($permissions);

        $successRoles = [];
        $failureRoles = [];

        foreach ($roles as $roleId)
        {
            $role = $this->repo->role->findByPublicIdAndOrgId($roleId, $orgId);

            $success = $this->core()->addPermissionsToRoles($role, $permissions);

            if ($success === true) {
                array_push($successRoles, $roleId);
            }
            else{
                array_push($failureRoles, $roleId);
            }
        }
        $data['success_roles'] = $successRoles;
        $data['fail_roles'] = $failureRoles;

        return $data;
    }
}
