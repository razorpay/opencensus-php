<?php

namespace RZP\Tests\Functional\Admin;

use RZP\Models\Admin\Role;
use RZP\Models\Admin\Permission;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class RoleTest extends TestCase
{
    use RequestResponseFlowTrait;
    use HeimdallTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/RoleData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org');

        $this->addAssignablePermissionsToOrg($this->org);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());
    }

    public function testCreateRole()
    {
        return $this->startTest();
    }

    public function testCreateRoleWithPermissions()
    {
        $permIds = $this->getPermissionsByIds('assignable');

        $this->testData[__FUNCTION__]['request']['content']['permissions'] = $permIds;

        $this->startTest();
    }

    public function testEditRoleDeleteAllPermissions()
    {
        $role = $this->fixtures->create('role', ['org_id' => $this->org->getId()]);

        $perms = $this->fixtures->times(3)->create('permission');

        $func = function($p) {
            return $p->getId();
        };

        $permIds = array_map($func, $perms);

        $role->permissions()->sync($permIds);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $role->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->testData[__FUNCTION__]['request']['content']['name'] = $role->getName();

        $this->startTest();

        $this->assertEquals(0, count($role->permissions->all()));
    }

    public function testEditRoleEditPermissions()
    {
        $role = $this->fixtures->create('role', ['org_id' => $this->org->getId()]);

        $oldPerms = array_slice($this->getPermissionsByIds('assignable'), 0, 3);

        $unsignedOldPerms = $oldPerms;

        $unsignedOldPerms = Permission\Entity::verifyIdAndStripSignMultiple($unsignedOldPerms);

        $role->permissions()->sync($unsignedOldPerms);

        $newPerm = $this->getPermissionsByIds('assignable')[5];

        $request = $this->testData[__FUNCTION__]['request'];

        $url = $request['url'];

        $url = sprintf($url, $role->getPublicId());

        $request['url'] = $url;

        $request['content']['name'] = $role->getName();

        $expectedPermissionIds = [$oldPerms[0], $newPerm];

        $request['content']['permissions'] = $expectedPermissionIds;

        $this->testData[__FUNCTION__]['request'] = $request;

        $this->startTest();

        $savedPermissions = $role->permissions->all();

        $func = function($p) {
            return $p->getPublicId();
        };

        $savedPermissionIds = array_map($func, $savedPermissions);

        $this->assertEquals(count(array_intersect($savedPermissionIds, $expectedPermissionIds)),
                            count(array_intersect($expectedPermissionIds, $savedPermissionIds)));
    }

    public function testAddPermissionsToRole()
    {
        $role = $this->fixtures->create('role', ['org_id' => $this->org->getId()]);

        $oldPerms = array_slice($this->getPermissionsByIds('assignable'), 0, 4);

        $unsignedOldPerms = $oldPerms;

        $unsignedOldPerms = Permission\Entity::verifyIdAndStripSignMultiple($unsignedOldPerms);

        $role->permissions()->sync($unsignedOldPerms);

        // 2 new permissions and 2 already assigned to role
        $newPerms = array_slice($this->getPermissionsByIds('assignable'), 2, 4);

        $request = $this->testData[__FUNCTION__]['request'];

        $request['content']['permissions'] = $newPerms;
        $request['content']['roles'] = [$role->getPublicId()];

        $this->testData[__FUNCTION__]['request'] = $request;

        $admin = $this->ba->getAdmin();

        $roleOfAdmin = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'edit_role_add_permissions']);

        $roleOfAdmin->permissions()->attach($perm->getId());

        $result = $this->startTest();

        $this->assertCount(1, $result['success_roles']);
        $this->assertCount(0, $result['fail_roles']);
        $this->assertContains($role->getId(), $result['success_roles']);

        $orgId = $this->org->getId();
        $rolePublicId = $role->getPublicId();
        $roleFromDb = (new Role\Repository())->findByPublicIdAndOrgId($rolePublicId, $orgId);

        $savedPermissions = $roleFromDb->permissions->all();

        $func = function($p) {
            return $p->getPublicId();
        };

        $savedPermissionIds = array_map($func, $savedPermissions);

        $expectedPermissionIds = array_unique(array_merge($oldPerms, $newPerms));

        sort($savedPermissionIds);

        sort($expectedPermissionIds);

        $this->assertEquals($expectedPermissionIds, $savedPermissionIds);
    }

    public function testGetRole()
    {
        $role = $this->getEntityById('role', Org::ADMIN_ROLE, true);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $role['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $result = $this->startTest();

        $this->assertEquals(
            $this->getTotalPermissionCount(), count($result['permissions']));
    }

    public function testDeleteRole()
    {
        $role = $this->fixtures->create('role', ['org_id' => $this->org->getId()]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $role->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditRole()
    {
        $role = $this->fixtures->create('role', ['org_id' => $this->org->getId()]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $role->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditSuperAdminRole()
    {
        $admin =  $this->ba->getAdmin();

        $role = $admin->roles[0];

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $role->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditSuperAdminRoleByRazorpay()
    {
        $role = $this->getEntityById('role', Org::ADMIN_ROLE, true);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $role['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetMultipleRoles()
    {
        $result = $this->startTest();

        $this->assertEquals(1, $result['count']);
    }

    public function testDuplicateRole()
    {
        $name = 'asd';
        $role = $this->fixtures->create(
            'role',
            ['org_id' => $this->org->getId(), 'name' => $name]);

        $this->testData[__FUNCTION__]['request']['content']['name'] = $name;

        return $this->startTest();
    }
}
