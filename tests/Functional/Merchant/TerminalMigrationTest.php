<?php

namespace RZP\Tests\Functional\Merchant;


use Mockery;
use RZP\Models\Terminal;
use RZP\Constants\Entity;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;


class TerminalMigrationTest extends TestCase
{
    use PaymentTrait;

    protected $razorxValue = RazorXClient::DEFAULT_CASE;

    protected $merchant;

    protected $terminalsServiceMock;

    protected $terminalRepository;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/TerminalMigrationTestData.php';

        parent::setUp();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['getTreatment'])
                            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    return $this->razorxValue;

                }) );

        $this->terminalsServiceMock = Mockery::mock('RZP\Services\TerminalsService')->makePartial();

        $this->terminalsServiceMock->shouldAllowMockingProtectedMethods();

        $this->app['terminals_service'] = $this->terminalsServiceMock;

        $this->app['config']->set('terminals_service.test.url', 'https://terminals-test.razorpay.com/');
        $this->app['config']->set('terminals_service.live.url', 'https://terminals-live.razorpay.com/');

        $this->merchant = $this->fixtures->create('merchant');

        $this->terminalRepository = new Terminal\Repository;

        $this->ba->adminAuth();
    }

    protected function mockTerminalsServiceSendRequest($closure, $times = 2)
    {
        $this->terminalsServiceMock->shouldReceive('sendRequest')
                                    ->times($times)
                                    ->andReturnUsing($closure);
    }

    public function testAssignTerminalTerminalServiceUpMigrateTerminalVariant()
    {
        $this->mockTerminalsServiceSendRequest(function($a, $b, $c) {
            $terminal = $this->getLastEntity(Entity::TERMINAL, true);

            Terminal\Entity::verifyIdAndSilentlyStripSign($terminal['id']);

            $terminalEntity = $this->terminalRepository->findOrFail($terminal['id']);

            $response =  new \Requests_Response;

            $responseData = ['data' => $terminalEntity->toArrayWithPassword()];

            $response->body = json_encode($responseData);

            return $response;
        });

        $this->razorxValue = 'on';

        $url = '/merchants/'. $this->merchant->getKey(). '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $response = $this->startTest();

        $terminalEntity = $this->terminalRepository->findOrFail($response['id']);

        $this->assertEquals(Terminal\SyncStatus::SYNC_SUCCESS, $terminalEntity->getSyncStatus());
    }

    public function testAssignTerminalControlVariant()
    {
        $this->mockTerminalsServiceSendRequest(null, 0);

        $url = '/merchants/'. $this->merchant->getKey(). '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $response = $this->startTest();

        $terminalEntity = $this->terminalRepository->findOrFail($response['id']);

        $this->assertEquals(Terminal\SyncStatus::NOT_SYNCED, $terminalEntity->getSyncStatus());
    }

}
