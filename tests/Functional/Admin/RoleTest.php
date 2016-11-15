<?php

namespace RZP\Tests\Functional\Admin;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

use Mockery;

class RoleTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/RoleData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org');
    }

    public function testCreateRole()
    {
        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->appAuth();

        return $this->startTest();
    }

    public function testCreateRoleWithPermissions()
    {
        $perms = [];

        $perms[] = $this->fixtures->create('permission');

        $perms[] = $this->fixtures->create('permission', ['name' => 'lol']);

        $permIds = [];

        foreach ($perms as $perm)
        {
            $permIds[] = $perm->getPublicId();
        }

        $this->testData[__FUNCTION__]['request']['content']['permissions'] = $permIds;

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testGetRole()
    {
        $role = $this->fixtures->create('role', ['org_id' => $this->org->getId()]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId(), $role->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->appAuth();

        $result = $this->startTest();
    }

    public function testDeleteRole()
    {
        $role = $this->fixtures->create('role', ['org_id' => $this->org->getId()]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId(), $role->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->appAuth();

        $result = $this->startTest();
    }

    public function testEditRole()
    {
        $role = $this->fixtures->create('role', ['org_id' => $this->org->getId()]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId(), $role->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->appAuth();

        $result = $this->startTest();
    }
}
