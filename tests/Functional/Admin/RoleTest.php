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
    }

    public function testCreateRole()
    {
        // Ideally the org should be creating the role through an admin
        // That admin will have the permission to create roles
        $this->ba->appAuth();

        $this->startTest();
    }
}
