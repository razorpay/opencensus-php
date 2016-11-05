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
        $this->ba->appAuth();

        $this->startTest();
    }
}
