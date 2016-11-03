<?php

namespace RZP\Tests\Functional\Admin;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

use Mockery;

class OrgTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/OrgData.php';

        parent::setUp();
    }

    public function testCreateOrg()
    {
        // Organization creation has to be done through rzp auth
        $this->ba->appAuth();

        $this->startTest();
    }
}
