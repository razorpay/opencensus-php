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

    protected function setUp(): void
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

        $this->testData[__FUNCTION__]['request']['content']['workflow_orgs'] = $orgs;

        $result = $this->startTest();

        $permId = Permission\Entity::verifyIdAndStripSign($result['id']);

        $permIds = $this->org->permissions()->allRelatedIds()->toArray();

        $workflowPermIds = $this->org->workflow_permissions()->allRelatedIds()->toArray();

        $this->assertContains($permId, $workflowPermIds);

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

        $orgPerms = $this->org->permissions()->allRelatedIds()->toArray();

        $this->assertContains($perm->getId(), $orgPerms);

        $rolePerms = $role->permissions()->allRelatedIds()->toArray();

        $this->assertCount(1, $rolePerms);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = $url . '/' . $perm->getPublicId();

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $orgPerms = $this->org->permissions()->allRelatedIds()->toArray();

        $this->assertNotContains($perm->getId(), $orgPerms);

        $rolePerms = $role->permissions()->allRelatedIds()->toArray();

        $this->assertCount(0, $rolePerms);

    }

    public function testGetMultipleForRazorpayOrg()
    {
        $this->ba->adminAuth();

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

    /**
     * Testing editing org permission with workflow Org and Org.
     * Here default org is also added as workflow org in request.
     */
    public function testEditPermissionWithOrg()
    {
        // Create a permission.
        $perm = $this->fixtures->create('permission');

        $perm->orgs()->attach($this->org->getId());

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = $url . '/' . $perm->getPublicId();

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();

        $permId = Permission\Entity::verifyIdAndStripSign($result['id']);

        $permIds = $this->org->permissions()->allRelatedIds()->toArray();

        $rzpOrg = (new OrgRepo)->findOrFailPublic(Org::RZP_ORG);

        $rzpPerms = $rzpOrg->permissions()->allRelatedIds()->toArray();

        $rzpWorkflowPerms = $rzpOrg->workflow_permissions()->allRelatedIds()->toArray();

        $this->assertNotContains($permId, $permIds);

        // asserting removal of workflow permission.
        $this->assertContains($permId, $rzpWorkflowPerms);

        $this->assertContains($permId, $rzpPerms);
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

    public function testGetMultipleWorkflowPermForRazorpayOrg()
    {
        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $rzpOrg = (new OrgRepo)->findOrFailPublic(Org::RZP_ORG);

        $workflowPermissionCount = $rzpOrg->workflow_permissions()->count();

        $this->testData[__FUNCTION__]['response']['content']['count'] = $workflowPermissionCount;

        $this->startTest();
    }

    public function testGetPermissionsByType()
    {
        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->setPermissionType('assignable');

        $this->startTest();

        $this->setPermissionType('all');

        $this->startTest();
    }

    public function setPermissionType($type)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $function = $trace[1]['function'];

        $url = '/permissions/get/' . $type;

        $this->testData[$function]['request']['url'] = $url;

        if ($type === 'assignable')
        {
            $permissionCount = count($this->getPermissions($type));
        }
        else
        {
            $permissionCount = $this->getTotalPermissionCount();
        }

        $this->testData[$function]['response']['content']['count'] = $permissionCount;
    }
}
