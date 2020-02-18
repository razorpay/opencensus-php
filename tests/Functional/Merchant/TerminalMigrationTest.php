<?php

namespace RZP\Tests\Functional\Merchant;


use Mockery;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Constants\Entity;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\IntegrationException;
use Symfony\Component\HttpFoundation\Response;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;


class TerminalMigrationTest extends TestCase
{
    use PaymentTrait;

    protected $razorxValue = RazorXClient::DEFAULT_CASE;

    protected $merchant;

    protected $terminalsServiceMock;

    protected $terminalRepository;

    //TODO add guide to tests

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

    protected function getTerminalToArrayPassword($terminalId)
    {
        Terminal\Entity::verifyIdAndSilentlyStripSign($terminalId);

        $terminalEntity = $this->terminalRepository->findOrFail($terminalId);

        return $terminalEntity->toArrayWithPassword();
    }

    protected function getDefaultTerminalServiceResponse($data = []) : \Requests_Response
    {
        if ($data === [])
        {
            $terminal = $this->getLastEntity(Entity::TERMINAL, true);

            $data = $this->getTerminalToArrayPassword($terminal[Terminal\Entity::ID]);

            Terminal\Entity::verifyIdAndSilentlyStripSign($data['id']);
        }

        $response =  new \Requests_Response;

        $responseData = ['data' => $data];

        $response->body = json_encode($responseData);

        return $response;
    }

    // the below cases tests migration functionality when a new terminal is created
    public function testAssignTerminalTerminalServiceUpMigrateTerminalVariant()
    {
        $this->mockTerminalsServiceSendRequest(function($a, $b, $c) {
           return $this->getDefaultTerminalServiceResponse();
        });

        $this->razorxValue = 'on';

        $url = '/merchants/'. $this->merchant->getKey(). '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $response = $this->startTest();

        $terminalEntity = $this->terminalRepository->findOrFail($response['id']);

        $this->assertEquals(Terminal\SyncStatus::SYNC_SUCCESS, $terminalEntity->getSyncStatus());
    }

    public function testAssignTerminalTerminalServiceDownMigrateTerminalVariant()
    {
        $this->mockTerminalsServiceSendRequest(function () {
            throw new \Requests_Exception_Transport_cURL('curl timed out', 1);
        }, 1);

        $this->razorxValue = 'on';

        $url = '/merchants/'. $this->merchant->getKey(). '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->expectException(\Requests_Exception_Transport_cURL::class);

        $this->expectExceptionMessage('curl timed out');

        $this->startTest();
    }

    public function testAssignTerminalServiceSuccessResponseBadValuesMigrateTerminalVariant()
    {
        $this->mockTerminalsServiceSendRequest(function() {

            $terminal = $this->getLastEntity(Entity::TERMINAL, true);

            Terminal\Entity::verifyIdAndSilentlyStripSign($terminal['id']);

            $data = $this->getTerminalToArrayPassword($terminal['id']);

            $data['gateway_terminal_password'] = '654321'; // its expected to be 123456

            return $this->getDefaultTerminalServiceResponse($data);
        });

        $this->razorxValue = 'on';

        $url = '/merchants/'. $this->merchant->getKey(). '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->expectException(IntegrationException::class);

        $this->expectExceptionMessage('field mismatch');

        $this->startTest();

    }

    public function testAssignTerminalsServiceFailureResponseMigrateTerminalVariant()
    {
        $this->mockTerminalsServiceSendRequest(function () {
            $response = $this->getDefaultTerminalServiceResponse();

            $response->body = '';

            $response->status_code = Response::HTTP_UNAUTHORIZED;

            throw new IntegrationException('Terminals service request failed with status code : ' . $response->status_code,
                ErrorCode::SERVER_ERROR_TERMINALS_SERVICE_INTEGRATION_ERROR,
                [
                    'response' => []
                ]
            );
        }, 1);

        $this->razorxValue = 'on';

        $url = '/merchants/'. $this->merchant->getKey(). '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->expectException(IntegrationException::class);

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_TERMINALS_SERVICE_INTEGRATION_ERROR);

        $this->expectExceptionMessage('401');

        $this->startTest();
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

    // the below cases tests migration functionality when an attribute of an existing terminal is tested
    public function testUpdateTerminalTerminalsServiceUpMigrateTerminalVariant()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', ['used' => true, 'enabled' => '1']);

        $tid = $terminal['id'];

        $url = '/terminals/'.$tid.'/toggle';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->razorxValue = 'on';

        $this->mockTerminalsServiceSendRequest(function () use ($tid){

            $data = $this->getTerminalToArrayPassword($tid);

            return $this->getDefaultTerminalServiceResponse($data);
        });

        $this->startTest();

        $terminalEntity = $this->terminalRepository->findOrFail($tid);

        $this->assertEquals(Terminal\SyncStatus::SYNC_SUCCESS, $terminalEntity->getSyncStatus());

        $this->assertFalse($terminalEntity->isEnabled());
    }


    public function testUpdateTerminalControlVariant()
    {
        $this->mockTerminalsServiceSendRequest(null, 0);

        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', ['used' => true, 'enabled' => '1']);

        $tid = $terminal['id'];

        $url = '/terminals/'.$tid.'/toggle';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->razorxValue = 'control';

        $this->startTest();

        $terminalEntity = $this->terminalRepository->findOrFail($tid);

        $this->assertEquals(Terminal\SyncStatus::NOT_SYNCED, $terminalEntity->getSyncStatus());
    }
}
