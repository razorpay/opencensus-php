<?php

namespace RZP\Tests\Functional\Dispute\Reason;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class DisputeReasonTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/DisputeReasonTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testReasonCreate()
    {
        $this->startTest();
    }

    public function testDisputeCreateWithMissingArgument()
    {
        $this->startTest();
    }

    public function testDisputeCreateWithInvalidNetwork()
    {
        $this->startTest();
    }

    public function testDisputeCreateWithExtraLongDescription()
    {
        $this->startTest();
    }
}
