<?php

namespace RZP\Tests\Functional\Admin;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class PermissionTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/PermissionData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org', [
            'email'         => 'random@rzp.com',
            'email_domains' => 'rzp.com',
        ]);

        $this->orgId = $this->org->getId();

        $this->ba->adminAuth();
    }

    public function testGetPermission()
    {
        $perm = $this->fixtures->create(
            'permission', ['name' => 'test permission']);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = $url . '/'. $perm->getPublicId();

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreatePermission()
    {
        $this->startTest();
    }

    public function testDeletePermission()
    {
        $perm = $this->fixtures->create(
            'permission');

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = $url . '/' . $perm->getPublicId();

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testGetMultiple()
    {
        $perm = $this->fixtures->times(2)->create(
            'permission', ['category' => 'test cat2']);

        $this->startTest();
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
}
