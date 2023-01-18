<?php

namespace RZP\Tests\Functional\Helpers\Heimdall;

use Carbon\Carbon;
use Config;
use Hash;

use RZP\Models\Admin\Permission;

/**
 * IMPORT requestResponseTrait explicitly in all the tests which
 * require heimdall trait
 */
trait HeimdallTrait
{
    protected function deleteAdmin($orgId, $adminId, $token = null, $mode = 'test')
    {
        $request = [
            'url'    => '/admin/' . $adminId,
            'method' => 'DELETE'
        ];

        $this->ba->adminAuth($mode, $token, $orgId);

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function getAdmin($orgId, $adminId, $token = null, $mode = 'test')
    {
        $request = [
            'url'    => '/admin/' . $adminId . '/fetch',
            'method' => 'GET'
        ];

        $this->ba->adminAuth($mode, $token, $orgId);

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    /**
     * Edit an admin as superadmin
     */
    protected function editAdmin($orgId, $adminId, $content = [], $mode = 'test')
    {
        $defaultContent = [
            'name' => 'Test Name',
        ];

        $content = array_merge($defaultContent, $content);

        $request = [
            'url'     => '/admin/' . $adminId,
            'method'  => 'PUT',
            'content' => $content,
        ];

        $this->ba->adminAuth($mode, null, $orgId);

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
            'email'    => 'admin@razorpay.com'
        ]);

        if ($role === 'admin')
        {
            $superAdminRole = $this->fixtures->create('role:admin_role', [
                'org_id' => $org->getId(),
            ]);

            $admin->roles()->attach($superAdminRole);
        }

        $bearerToken = 'ThisIsATokenFORAdmin';

        $adminToken = $this->fixtures->create('admin_token', [
            'admin_id'   => $admin->getId(),
            'token'      => Hash::make($bearerToken),
            'created_at' => $now->timestamp,
            'expires_at' => $now->addDays(2)->timestamp,
        ]);

        return $bearerToken . $adminToken->getId();
    }

    public function adminForgotPassword($email)
    {
        $request = [
            'url'     => '/admin/forgot_password',
            'method'  => 'POST',
            'content' => [
                'email' => $email,
                'reset_password_url' => 'hello.com',
            ],
        ];

        $this->ba->dashboardGuestAppAuth($this->hostName);

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
            'url' => '/field-map',
            'method' => 'POST',
            'content' => [
                'entity_name' => $entity,
                'org_id' => $orgId,
                'fields' => $fields
            ],
        ];

        $this->ba->adminAuth('test', $token, $orgId);

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    public function getPermissions($type = 'assignable')
    {
        $permissions = Config::get('heimdall.permissions');

        $specificPermissions = [];

        foreach ($permissions as $permCategory)
        {
            foreach ($permCategory as $permission => $permissionValue)
            {
                if (isset($permissionValue[$type]) and $permissionValue[$type])
                {
                    $specificPermissions[] = $permission;
                }
            }
        }

        return $specificPermissions;
    }

    public function getTotalPermissionCount()
    {
        $permissionCategories = Config::get('heimdall.permissions');

        $permissionCount = 0;

        foreach ($permissionCategories as $permCategory)
        {
            foreach ($permCategory as $permission => $desc)
            {
                $permissionCount++;
            }
        }

        return $permissionCount;
    }

    public function getPermissionsByIds($type = 'assignable')
    {
        $perms = $this->getPermissions($type);

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
        $perms = $this->getPermissionsByIds('assignable');

        Permission\Entity::verifyIdAndStripSignMultiple($perms);

        $org->permissions()->sync($perms);
    }

    public function addWorkflowPermissionsToOrg($org)
    {
        $perms = $this->getPermissionsByIds('workflow');

        Permission\Entity::verifyIdAndStripSignMultiple($perms);

        // Enabling workflow permissions for that org.
        (new Permission\Repository)->toggleWorkflowOnOrgForPermissions(
            $org->getId(),
            $perms,
            true
            );
    }
}
