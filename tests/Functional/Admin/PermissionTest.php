<?php

namespace RZP\Tests\Functional\Admin;

use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

use RZP\Models\Admin\Org\Repository as OrgRepo;
use RZP\Models\Admin\Permission;

class PermissionTest extends TestCase
{
    use RequestResponseFlowTrait;
    use HeimdallTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/PermissionData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org', [
            'email'         => 'random@rzp.com',
            'email_domains' => 'rzp.com',
        ]);

        $this->addAssignablePermissionsToOrg($this->org);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());
    }

    public function testGetPermission()
    {
        $perm = $this->fixtures->create(
            'permission', ['name' => 'test permission']);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = $url . '/'. $perm->getPublicId();

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();

        $this->assertArrayHasKey('orgs', $result);
    }

    public function testCreatePermission()
    {
        $this->startTest();
    }

    public function testCreatePermissionWithOrg()
    {
        $orgs = [$this->org->getPublicId()];

        $this->testData[__FUNCTION__]['request']['content']['orgs'] = $orgs;

        $result = $this->startTest();

        $permId = Permission\Entity::verifyIdAndStripSign($result['id']);

        $permIds = $this->org->permissions()->getRelatedIds()->toArray();

        $this->assertContains($permId, $permIds);
    }

    public function testDeletePermission()
    {
        $perm = $this->fixtures->create(
            'permission', ['name' => 'test perm']);

        $perm->orgs()->attach($this->org);

        $role = $this->fixtures->create(
            'role',
            ['org_id' => $this->org->getId(), 'name' => 'test name']);

        $perm->roles()->attach($role);

        $orgPerms = $this->org->permissions()->getRelatedIds()->toArray();

        $this->assertContains($perm->getId(), $orgPerms);

        $rolePerms = $role->permissions()->getRelatedIds()->toArray();

        $this->assertCount(1, $rolePerms);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = $url . '/' . $perm->getPublicId();

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $orgPerms = $this->org->permissions()->getRelatedIds()->toArray();

        $this->assertNotContains($perm->getId(), $orgPerms);

        $rolePerms = $role->permissions()->getRelatedIds()->toArray();

        $this->assertCount(0, $rolePerms);

    }

    public function testGetMultipleForRazorpayOrg()
    {
        $this->ba->adminAuth();

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $orgId = 'org_' . Org::RZP_ORG;

        $url = sprintf($url, $orgId);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();

        $this->assertEquals($this->getTotalPermissionCount(), $result['count']);
    }

    public function testEditPermission()
    {
        $perm = $this->fixtures->create(
            'permission');

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = $url . '/' . $perm->getPublicId();

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditPermissionWithOrg()
    {
        $perm = $this->fixtures->create(
            'permission');

        (new Permission\Repository)->attach($perm, 'orgs', [$this->org->getId()]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = $url . '/' . $perm->getPublicId();

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();

        $permId = Permission\Entity::verifyIdAndStripSign($result['id']);

        $permIds = $this->org->permissions()->getRelatedIds()->toArray();

        $rzpOrg = (new OrgRepo)->findOrFailPublic(Org::RZP_ORG);

        $rzpPerms = $rzpOrg->permissions()->getRelatedIds()->toArray();

        $this->assertNotContains($permId, $permIds);

        $this->assertContains($permId, $rzpPerms);

        $this->startTest();
    }

    public function testGetRolesForPermission()
    {
        $role = $this->fixtures->create(
            'role',
            ['org_id' => $this->org->getId(), 'name' => 'test name']);

        $perms = ['edit_admin'];

        $perm = (new Permission\Repository)->retrieveIdsByNames($perms)[0];

        $role->permissions()->attach($perm);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $perm->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();

        $this->assertCount(2, $result['items']);
    }
}
