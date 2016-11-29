<?php

namespace RZP\Tests\Functional\Admin;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

use RZP\Tests\Functional\Fixtures\Entity\Org;

use Mockery;

class RoleTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/RoleData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org');

        $this->ba->adminAuth('test');
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
        $perms = $this->fixtures->times(3)->create('permission');

        $permIds = [];

        foreach ($perms as $perm)
        {
            $permIds[] = $perm->getPublicId();
        }

        $this->testData[__FUNCTION__]['request']['content']['permissions'] = $permIds;

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();
    }

    public function testGetRole()
    {
        $role = $this->getEntityById('role', Org::ADMIN_ROLE, true);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, 'org_' . Org::RZP_ORG, $role['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $result = $this->startTest();

        $this->assertEquals(count($result['permissions']), 105);
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

        $result = $this->startTest();
    }
}
