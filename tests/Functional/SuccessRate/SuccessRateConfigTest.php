<?php

namespace RZP\Tests\Functional\SuccessRate;

use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class SuccessRateConfigTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/SuccessRateConfigTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testSuccessRateGetConfig()
    {
        $this->startTest();
    }
}
