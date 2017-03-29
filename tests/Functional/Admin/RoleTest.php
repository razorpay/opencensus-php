<?php

namespace RZP\Tests\Functional\Admin;

use RZP\Models\Admin\Permission;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\TestCase;

class RoleTest extends TestCase
{
    use HeimdallTrait;

    const TOTAL_PERMISSIONS = 114;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/RoleData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org');

        $this->addAssignablePermissionsToOrg($this->org);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken);
    }

    public function testCreateRole()
    {
        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        return $this->startTest();
    }

    public function testCreateRoleWithPermissions()
    {
        $permIds = $this->getAssignablePermissionsByIds();

        $this->testData[__FUNCTION__]['request']['content']['permissions'] = $permIds;

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();
    }

    public function testEditRoleDeleteAllPermissions()
    {
        $role = $this->fixtures->create('role', ['org_id' => $this->org->getId()]);

        $perms = $this->fixtures->times(3)->create('permission');

        $permIds = array_map(create_function('$p', 'return $p->getId();'), $perms);
        ;

        $role->permissions()->sync($permIds);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId(), $role->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->testData[__FUNCTION__]['request']['content']['name'] = $role->getName();

        $this->startTest();

        $this->assertEquals(0, count($role->permissions->all()));
    }

    public function testEditRoleEditPermissions()
    {
        $role = $this->fixtures->create('role', ['org_id' => $this->org->getId()]);

        $oldPerms = array_slice($this->getAssignablePermissionsByIds(), 0, 3);

        $unsignedOldPerms = $oldPerms;

        $unsignedOldPerms = Permission\Entity::verifyIdAndStripSignMultiple($unsignedOldPerms);

        $role->permissions()->sync($unsignedOldPerms);

        $newPerm = $this->getAssignablePermissionsByIds()[5];

        $request = $this->testData[__FUNCTION__]['request'];

        $url = $request['url'];

        $url = sprintf($url, $this->org->getPublicId(), $role->getPublicId());

        $request['url'] = $url;

        $request['content']['name'] = $role->getName();

        $expectedPermissionIds = [$oldPerms[0], $newPerm];

        $request['content']['permissions'] = $expectedPermissionIds;

        $this->testData[__FUNCTION__]['request'] = $request;

        $this->startTest();

        $savedPermissions = $role->permissions->all();
        $savedPermissionIds = array_map(create_function('$p', 'return $p->getPublicId();'),
                                                        $savedPermissions);

        $this->assertEquals(count(array_intersect($savedPermissionIds, $expectedPermissionIds)),
                            count(array_intersect($expectedPermissionIds, $savedPermissionIds)));
    }

    public function testGetRole()
    {
        $role = $this->getEntityById('role', Org::ADMIN_ROLE, true);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, 'org_' . Org::RZP_ORG, $role['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $result = $this->startTest();

        $this->assertEquals(self::TOTAL_PERMISSIONS, count($result['permissions']));
    }

    public function testDeleteRole()
    {
        $role = $this->fixtures->create('role', ['org_id' => $this->org->getId()]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId(), $role->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();
    }

    public function testEditRole()
    {
        $role = $this->fixtures->create('role', ['org_id' => $this->org->getId()]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId(), $role->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditSuperAdminRole()
    {
        $admin =  $this->ba->getAdmin();

        $role = $admin->roles[0];

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId(), $role->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditSuperAdminRoleByRazorpay()
    {
        $role = $this->getEntityById('role', Org::ADMIN_ROLE, true);

        $orgId = 'org_' . Org::RZP_ORG;

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $orgId, $role['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetMultipleRoles()
    {
        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();

        $this->assertEquals(1, $result['count']);
    }

    public function testDuplicateRole()
    {
        $name = 'asd';
        $role = $this->fixtures->create(
            'role',
            ['org_id' => $this->org->getId(), 'name' => $name]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->testData[__FUNCTION__]['request']['content']['name'] = $name;

        return $this->startTest();
    }
}
