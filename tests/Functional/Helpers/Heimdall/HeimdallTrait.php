<?php

namespace RZP\Tests\Functional\Helpers\Heimdall;

use Carbon\Carbon;
use Config;

use RZP\Models\Admin\Permission;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;

trait HeimdallTrait
{
    use EntityActionTrait;
    use RequestResponseFlowTrait
    {
        sendRequest as makeRequestParent;
    }

    protected function deleteAdmin($orgId, $adminId, $token = null)
    {
        $request = [
            'url'    => '/orgs/' . $orgId . '/admins/' . $adminId,
            'method' => 'DELETE'
        ];

        $this->ba->adminAuth('test', $token);

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function getAdmin($orgId, $adminId, $token = null)
    {
        $request = [
            'url'    => '/orgs/' . $orgId . '/admins/' . $adminId,
            'method' => 'GET'
        ];

        $this->ba->adminAuth('test', $token);

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function getAuthTokenForOrg($org, $role = 'admin')
    {
        $now = Carbon::now();

        $admin = $this->fixtures->create('admin', [
            'org_id'   => $org->getId(),
            'username' => 'auth admin',
            'password' => 'Heimdall!234',
        ]);

        if ($role === 'admin')
        {
            $superAdminRole = $this->fixtures->create('role:admin_role', [
                'org_id' => $org->getId(),
            ]);

            $admin->roles()->attach($superAdminRole);
        }

        $adminToken = $this->fixtures->create('admin_token', [
            'admin_id'   => $admin->getId(),
            'token'      => str_random(40),
            'created_at' => $now->timestamp,
            'expires_at' => $now->addDays(2)->timestamp,
        ]);

        return $adminToken->getToken();
    }

    public function adminForgotPassword($orgId, $email)
    {
        $request = [
            'url'     => '/orgs/' . $orgId . '/admin/forgot_password',
            'method'  => 'POST',
            'content' => [
                'email' => $email,
                'reset_password_url' => 'hello.com',
            ],
        ];

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    public function storeFieldsForEntity(
        string $orgId,
        string $entity,
        array $fields,
        string $token = null)
    {
        $request = [
            'url' => '/orgs/' . $orgId . '/field-map',
            'method' => 'POST',
            'content' => [
                'entity_name' => $entity,
                'org_id' => $orgId,
                'fields' => $fields
            ],
        ];

        $this->ba->adminAuth('test', $token);

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    public function getAssignablePermissions()
    {
        $assignablePermissions = Config::get('heimdall.assignablePermissions');

        $permissions = [];

        foreach ($assignablePermissions as $permCategory)
        {
            foreach ($permCategory as $permission => $desc)
            {
                $permissions[] = $permission;
            }
        }

        return $permissions;
    }

    public function getAssignablePermissionsByIds()
    {
        $perms = $this->getAssignablePermissions();

        $permissions = (new Permission\Repository)->retrieveIdsByNames($perms);

        $permissionIds = [];

        foreach ($permissions as $permission)
        {
            $permissionIds[] = $permission->getPublicId();
        }

        return $permissionIds;
    }

    public function addAssignablePermissionsToOrg($org)
    {
        $perms = $this->getAssignablePermissionsByIds();

        Permission\Entity::verifyIdAndStripSignMultiple($perms);

        $org->permissions()->sync($perms);
    }
}
