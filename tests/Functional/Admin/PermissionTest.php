<?php

namespace RZP\Tests\Functional\Admin;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

use Mockery;

class PermissionTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/PermissionData.php';

        parent::setUp();
    }

    public function testCreatePermission()
    {
        $this->markTestSkipped();

        $this->ba->appAuth();

        return $this->startTest();
    }

    public function testGetPermission()
    {
        $this->markTestSkipped();

        $permission = $this->testCreatePermission();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] .= '/' . $permission['id'];

        $this->ba->appAuth();

        $result = $this->startTest($testData);
    }
}
