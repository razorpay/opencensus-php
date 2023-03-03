<?php

namespace RZP\Tests\Functional\Dispute\Reason;

use RZP\Tests\Functional\TestCase;
use RZP\Services\Mock\DisputesClient;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class DisputeReasonTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/DisputeReasonTestData.php';

        parent::setUp();

        $this->ba->adminAuth();

        $this->setUpDisputeClientMock();
    }

    protected function setUpDisputeClientMock()
    {
        $mockDisputeClient = new DisputesClient();

        $this->app->instance('disputes', $mockDisputeClient);
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
