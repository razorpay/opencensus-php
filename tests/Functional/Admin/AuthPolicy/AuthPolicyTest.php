<?php

namespace RZP\Tests\Functional\Admin\AuthPolicy;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

use RZP\Models\Admin\Admin;

class AuthPolicyTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/AuthPolicyData.php';

        parent::setUp();
    }

    public function testAdminLogin()
    {
        $this->ba->appAuth('rzp_live');

        $this->startTest();
    }
}