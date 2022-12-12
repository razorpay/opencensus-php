<?php

namespace Functional\Dispute;

use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\Admin\Permission\Name as PermissionName;

class DisputeServiceTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $client;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/DisputeServiceTestData.php';

        parent::setUp();

        $this->app['config']->set('services.disputes.mock', true);
    }

    protected function updateFetchTestData(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $testData = &$this->testData[$name];

        return $testData;
    }

    protected function setUpForDisputeTest(int $disputeCreatedAt, string $returnValue = 'on')
    {
        $this->mockRazorxTreatment($returnValue);

        $this->ba->proxyAuth();

        $this->fixtures->create('dispute', [
            'id'                => '1000000dispute',
            'deduct_at_onset'   => 1,
            'created_at'        => $disputeCreatedAt,
        ]);
    }

    protected function mockRazorxTreatment(string $returnValue)
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn($returnValue);
    }

    public function testDisputeFetchProxyAuthViaDisputeClient()
    {
        $this->setUpForDisputeTest(1673326805);

        $testData = $this->updateFetchTestData();

        $this->assertEquals([], $this->runRequestResponseFlow($testData));
    }

    public function testDisputeFetchProxyAuthOlderDispute()
    {
        $this->setUpForDisputeTest(1673326799);

        $testData = $this->updateFetchTestData();

        $this->runRequestResponseFlow($testData);
    }

    public function testDisputeFetchProxyAuthExperimentOff()
    {
        $this->setUpForDisputeTest(1673326804, 'off');

        $testData = $this->updateFetchTestData();

        $this->runRequestResponseFlow($testData);
    }
}
