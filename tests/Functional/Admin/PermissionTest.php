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

        $this->ba->adminAuth('test');
    }

    public function testGetPermission()
    {
        $this->markTestSkipped();

        $permissions = $this->fixtures->times(2)->create('permission');

        $this->startTest();
    }
}
