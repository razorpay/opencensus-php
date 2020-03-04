<?php

namespace RZP\Tests\Functional\Merchant;


use Mockery;
use RZP\Constants\Table;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Constants\Entity;
use RZP\Http\Request\Requests;
use RZP\Services\RazorXClient;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\DB;
use RZP\Exception\IntegrationException;
use Symfony\Component\HttpFoundation\Response;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;


class TerminalMigrationTest extends TestCase
{
    use PaymentTrait;
    use TestsMetrics;

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

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);
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

    protected function getTerminalsServiceResponseForEntityNotFound()
    {
        $response = new \Requests_Response;

        $response->body = '
   {
    "data": null,
    "error": {
        "internal_error_code": "BAD_REQUEST_ERROR",
        "gateway_error_code": "",
        "gateway_error_description": "",
        "description": "invalid request sent"
    }
}';
        $response->status_code = Response::HTTP_BAD_REQUEST;

        return $response;
    }

    protected function getTerminalsServiceResponseForEntityDeleted()
    {
        $response = new \Requests_Response;

        $response->body = '{"data": null}';

        return $response;
    }

    protected function getDefaultTerminalServiceMerchantTerminalCreatedResponse()
    {
        return $this->getDefaultTerminalServiceResponse();
    }
    protected function getDefaultTerminalSubmerchantFetchResponse(string $terminalId, string $merchantId)
    {


        $response = new \Requests_Response;

        $format= '
       {
  "data": 
    [
      {
        "merchant_id": "%s",
        "terminal_id": "%s"
       }
   ]
}';
        $response->body = sprintf($format, $merchantId, $terminalId);

        return $response;
    }

    private function makePaymentAndGetTerminalId()
    {
        $this->doAuthAndCapturePayment();

        $payment = $this->getLastPayment(true);

        return $payment['terminal_id'];
    }

    private function getMerchantTerminalCount(string $merchantId, string $terminalId)
    {
        return Db::table(Table::MERCHANT_TERMINAL)
                  ->where(Terminal\Entity::MERCHANT_ID, $merchantId)
                  ->where(Terminal\Entity::TERMINAL_ID,  $terminalId)
                  ->count();
    }

    // the below cases tests migration functionality when a new terminal is created
    public function testAssignTerminalTerminalServiceUpMigrateTerminalVariant()
    {
        $this->mockTerminalsServiceSendRequest(function($a, $b, $c) {
           return $this->getDefaultTerminalServiceResponse();
        });

        $this->razorxValue = 'migrate';

        $url = '/merchants/'. $this->merchant->getKey(). '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $beforeCount = DB::table('terminals')->count();

        $response = $this->startTest();

        $terminalEntity = $this->terminalRepository->findOrFail($response['id']);

        $this->assertEquals(Terminal\SyncStatus::SYNC_SUCCESS, $terminalEntity->getSyncStatus());

        $afterCount = Db::table('terminals')->count();

        $this->assertEquals($beforeCount + 1, $afterCount);
    }

    public function testAssignTerminalTerminalServiceDownMigrateTerminalVariant()
    {
        $this->mockTerminalsServiceSendRequest(function () {
            throw new \Requests_Exception_Transport_cURL('curl timed out', 1);
        }, 1);

        $this->razorxValue = 'migrate';

        $url = '/merchants/'. $this->merchant->getKey(). '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->expectException(\Requests_Exception_Transport_cURL::class);

        $this->expectExceptionMessage('curl timed out');

        $beforeCount = DB::table('terminals')->count();

        $this->startTest();

        $afterCount = Db::table('terminals')->count();

        $this->assertEquals($beforeCount, $afterCount);
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

        $this->razorxValue = 'migrate';

        $url = '/merchants/'. $this->merchant->getKey(). '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->expectException(IntegrationException::class);

        $this->expectExceptionMessage('field mismatch');

        $beforeCount = DB::table('terminals')->count();

        $this->startTest();

        $afterCount = Db::table('terminals')->count();

        $this->assertEquals($beforeCount, $afterCount);
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

        $this->razorxValue = 'migrate';

        $url = '/merchants/'. $this->merchant->getKey(). '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->expectException(IntegrationException::class);

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_TERMINALS_SERVICE_INTEGRATION_ERROR);

        $this->expectExceptionMessage('401');

        $beforeCount = DB::table('terminals')->count();

        $this->startTest();

        $afterCount = Db::table('terminals')->count();

        $this->assertEquals($beforeCount, $afterCount);
    }

    public function testAssignTerminalControlVariant()
    {
        $this->mockTerminalsServiceSendRequest(null, 0);

        $url = '/merchants/'. $this->merchant->getKey(). '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $response = $this->startTest();

        $terminalEntity = $this->terminalRepository->findOrFail($response['id']);

        $beforeCount = DB::table('terminals')->count();

        $this->assertEquals(Terminal\SyncStatus::NOT_SYNCED, $terminalEntity->getSyncStatus());

        $afterCount = Db::table('terminals')->count();

        $this->assertEquals($beforeCount, $afterCount);
    }

    // the below cases tests migration functionality when an attribute of an existing terminal is tested
    public function testUpdateTerminalTerminalsServiceUpMigrateTerminalVariant()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', ['used' => true, 'enabled' => '1']);

        $tid = $terminal['id'];

        $url = '/terminals/'.$tid.'/toggle';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->razorxValue = 'migrate';

        $this->mockTerminalsServiceSendRequest(function () use ($tid){

            $data = $this->getTerminalToArrayPassword($tid);

            return $this->getDefaultTerminalServiceResponse($data);
        });

        $this->startTest();

        $terminalEntity = $this->terminalRepository->findOrFail($tid);

        $this->assertEquals(Terminal\SyncStatus::SYNC_SUCCESS, $terminalEntity->getSyncStatus());

        $this->assertFalse($terminalEntity->isEnabled());
    }

    public function testUpdateTerminalTerminalsServiceDownMigrateTerminalVariant()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', [
                'used' => true,
                'enabled' => '1',
                'sync_status' => Terminal\SyncStatus::SYNC_SUCCESS
            ]);

        $tid = $terminal['id'];

        $url = '/terminals/' . $tid . '/toggle';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->razorxValue = 'migrate';

        $this->mockTerminalsServiceSendRequest(function () use ($tid) {
            throw new \Requests_Exception_Transport_cURL('curl timed out', 1);
        }, 1);

        $this->expectException(\Requests_Exception_Transport_cURL::class);

        $this->expectExceptionMessage('timed out');

        $this->startTest();

        $terminalEntity = $this->terminalRepository->findOrFail($tid);

        $this->assertTrue($terminal->isEnabled());


        $this->assertEquals(Terminal\SyncStatus::SYNC_FAILED, $terminalEntity->getSyncStatus());
    }

    public function testUpdateTerminalServiceSuccessResponseBadValuesMigrateTerminalVariant()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', [
            'used' => true,
            'enabled' => '1',
            'sync_status' => Terminal\SyncStatus::SYNC_FAILED
        ]);

        $tid = $terminal['id'];

        $url = '/terminals/' . $tid . '/toggle';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->razorxValue = 'migrate';

        $this->mockTerminalsServiceSendRequest(function() use ($tid) {

            $data = $this->getTerminalToArrayPassword($tid);

            $data['enabled'] = '1'; // simulating a field mismatch that could be caused due to bug on terminals service

            return $this->getDefaultTerminalServiceResponse($data);
        });

        $this->expectException(IntegrationException::class);

        $this->expectExceptionMessage('field mismatch');

        $this->startTest();

        $this->assertTrue($terminal->isEnabled());

        // here we are asserting that sync status did not get updated from the previous value
        // the previous value was set while creating fixture('sync_status' => '3')
        $this->assertEquals(Terminal\SyncStatus::SYNC_FAILED, $terminal->getSyncStatus());
    }

    public function testUpdateTerminalServiceFailureResponseMigrateTerminalVariant()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', [
            'used' => true,
            'enabled' => '1',
            'sync_status' => Terminal\SyncStatus::SYNC_FAILED
        ]);

        $tid = $terminal['id'];

        $url = '/terminals/' . $tid . '/toggle';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->razorxValue = 'migrate';

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

        $this->expectException(IntegrationException::class);

        $this->expectExceptionMessage('401');

        $this->startTest();

        $this->assertTrue($terminal->isEnabled());

        // here we are asserting that sync status did not get updated from the previous value
        // the previous value was set while creating fixture('sync_status' => '3')
        $this->assertEquals(Terminal\SyncStatus::SYNC_FAILED, $terminal->getSyncStatus());
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

    // below are test cases for deleting a terminal. there are two types of delete for a terminal depending on whether payment
    // had happened on that terminal or not

    public function testDeleteTerminalNoPaymentTerminalsServiceUpMigrateVariant()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', ['used' => true, 'enabled' => '1']);

        $tid = $terminal['id'];

        $url = '/terminals/'.$tid;

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->razorxValue = 'migrate';

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($tid) {
            $response = new  \Requests_Response;

            $this->assertStringEndsWith('/' . $tid, $path);

            if ($method == \Requests::DELETE)
            {
                return $this->getTerminalsServiceResponseForEntityDeleted();
            }

            if ($method == \Requests::GET)
            {
                return $this->getTerminalsServiceResponseForEntityNotFound();
            }

        }, 2);

        $beforeCount = DB::table('terminals')->count();

        $this->startTest();

        $afterCount = DB::table('terminals')->count();

        $this->assertEquals($beforeCount - 1, $afterCount);

    }

    public function testDeleteTerminalNoPaymentTerminalsServiceDownMigrateVariant()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', [
                'used'        => true,
                'enabled'     => '1',
                'sync_status' => 'sync_success',
            ]);

        $tid = $terminal['id'];

        $url = '/terminals/'.$tid;

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->razorxValue = 'migrate';

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) {
            throw new \Requests_Exception_Transport_cURL('curl timed out', 1);
        }, 1);

        $this->expectException(\Requests_Exception_Transport_cURL::class);

        $this->expectExceptionMessage('curl');

        $beforeCount = DB::table('terminals')->count();

        $this->startTest();

        $afterCount = DB::table('terminals')->count();

        $this->assertEquals($beforeCount, $afterCount);

        $terminal = $this->terminalRepository->findOrFail($tid);

        $this->assertEquals(Terminal\SyncStatus::SYNC_SUCCESS, $terminal->getSyncStatus());
    }

    public function testDeleteTerminalNoPaymentTerminalsServiceUpBadResponseOnTerminalFetchMigrateVariant()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', [
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
        ]);

        $tid = $terminal['id'];

        $url = '/terminals/'.$tid;

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->razorxValue = 'migrate';

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($tid) {

            $this->assertStringEndsWith('/' . $tid, $path);

            if ($method == \Requests::DELETE)
            {
                return $this->getTerminalsServiceResponseForEntityDeleted();
            }

            if ($method == \Requests::GET)
            {
                return $this->getDefaultTerminalServiceResponse();
            }
        }, 2);

        $this->expectException(IntegrationException::class);

        $this->expectExceptionMessage('got non empty response when fetching a deleted terminal');

        $beforeCount = DB::table('terminals')->count();

        $this->startTest();

        $afterCount = DB::table('terminals')->count();

        $this->assertEquals($beforeCount, $afterCount);

        $terminal = $this->terminalRepository->findOrFail($tid);

        $this->assertEquals(Terminal\SyncStatus::SYNC_SUCCESS, $terminal->getSyncStatus());
    }

    public function testDeleteTerminalNoPaymentControlVariant()
    {
        $this->mockTerminalsServiceSendRequest(null, 0);

        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', ['used' => true, 'enabled' => '1']);

        $tid = $terminal['id'];

        $url = '/terminals/'.$tid;

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->razorxValue = 'control';

        $beforeCount = DB::table('terminals')->count();

        $this->startTest();

        $afterCount = DB::table('terminals')->count();

        $this->assertEquals($beforeCount - 1, $afterCount);
    }


    // tests for delete terminal + migration on a terminal which has a payment
    public function testDeleteTerminalWithPaymentTerminalsServiceUpMigrateVariant()
    {
        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) {
            if ($method === \Requests::DELETE)
            {
                return $this->getTerminalsServiceResponseForEntityDeleted();
            }

            if ($method === \Requests::GET)
            {
                return $this->getTerminalsServiceResponseForEntityNotFound();
            }
        }, 2);

        $tid = $this->makePaymentAndGetTerminalId();

        $terminal = $this->terminalRepository->findOrFail($tid);

        $terminal->setSyncStatus(Terminal\SyncStatus::NOT_SYNCED);

        $terminal->saveOrFail();

        $this->razorxValue = 'migrate';

        $url = '/terminals/'.$tid;

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $beforeCount = DB::table('terminals')->count();

        $this->startTest();

        $afterCount = DB::table('terminals')->count();

        $this->assertEquals($beforeCount, $afterCount);

        $terminalEntity = DB::table('terminals')->where('id', '=', $tid)->first(); // stdClass object

        $this->assertNotNull($terminalEntity->deleted_at);

        $this->assertEquals(Terminal\SyncStatus::getValueForSyncStatusString(Terminal\SyncStatus::SYNC_SUCCESS),
            $terminalEntity->sync_status);
    }

    public function testDeleteTerminalWithPaymentTerminalsServiceDownMigrateVariant()
    {
        $tid = $this->makePaymentAndGetTerminalId();

        $terminal = $this->terminalRepository->findOrFail($tid);

        $terminal->setSyncStatus(Terminal\SyncStatus::NOT_SYNCED);

        $terminal->saveOrFail();

        $url = '/terminals/'.$tid;

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->razorxValue = 'migrate';

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) {
            throw new \Requests_Exception_Transport_cURL('curl timed out', 1);
        }, 1);

        $this->expectException(\Requests_Exception_Transport_cURL::class);

        $this->expectExceptionMessage('curl');

        $beforeCount = DB::table('terminals')->count();

        $this->startTest();

        $afterCount = DB::table('terminals')->count();

        $this->assertEquals($beforeCount, $afterCount);

        $terminal = $this->terminalRepository->findOrFail($tid);

        // at the start of test, we set sync status of terminal to not_synced.
        // we are asserting that the value hasnt changed.
        $this->assertEquals(Terminal\SyncStatus::NOT_SYNCED, $terminal->getSyncStatus());

        $this->assertNotNull($terminal->getDeletedAt());
    }

    public function testDeleteTerminalWithPaymentTerminalsServiceUpBadResponseMigrateVariant()
    {
        $tid = $this->makePaymentAndGetTerminalId();

        $terminal = $this->terminalRepository->findOrFail($tid);

        $terminal->setSyncStatus(Terminal\SyncStatus::NOT_SYNCED);

        $terminal->saveOrFail();

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($tid) {

            $this->assertStringEndsWith('/' . $tid, $path);

            if ($method == \Requests::DELETE)
            {
                return $this->getTerminalsServiceResponseForEntityDeleted();
            }

            if ($method == \Requests::GET)
            {
                return $this->getDefaultTerminalServiceResponse();
            }
        }, 2);

        $url = '/terminals/'.$tid;

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->razorxValue = 'migrate';

        $this->expectException(IntegrationException::class);

        $this->expectExceptionMessage('got non empty response when fetching a deleted terminal');

        $beforeCount = DB::table('terminals')->count();

        $this->startTest();

        $afterCount = DB::table('terminals')->count();

        $this->assertEquals($beforeCount, $afterCount);

        $terminal = $this->terminalRepository->findOrFail($tid);

        $this->assertNotNull($terminal->getDeletedAt());

        $this->assertEquals(Terminal\SyncStatus::NOT_SYNCED, $terminal->getSyncStatus());
    }

    public function testDeleteTerminalWithPaymentControlVariant()
    {
        $this->mockTerminalsServiceSendRequest(null, 0);

        $tid = $this->makePaymentAndGetTerminalId();

        $url = '/terminals/'.$tid;

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->razorxValue = 'control';

        $beforeCount = DB::table('terminals')->count();

        $this->startTest();

        $afterCount = DB::table('terminals')->count();

        $this->assertEquals($beforeCount, $afterCount);

        $terminalEntity = DB::table('terminals')->where('id', '=', $tid)->first(); // stdClass object

        $this->assertNotNull($terminalEntity->deleted_at);

        $this->assertEquals(Terminal\SyncStatus::getValueForSyncStatusString(Terminal\SyncStatus::NOT_SYNCED),
                            $terminalEntity->sync_status);
    }

    // tests for syncing merchant_terminal pivot row when submerchants are added to
    // or removed from a terminal


    public function testAddSubmerchantControlVariant()
    {
        $this->razorxValue = 'control';

        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', [
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
        ]);


        $this->mockTerminalsServiceSendRequest(null, 0);

        $beforeCount = $this->getMerchantTerminalCount('10000000000000', $terminal['id']);

        $this->assignSubMerchant($terminal['id'], '10000000000000');

        $afterCount = $this->getMerchantTerminalCount('10000000000000', $terminal['id']);

        $this->assertEquals($beforeCount + 1, $afterCount);

        return [$terminal['id'], '10000000000000'];
    }

    public function testAddSubmerchantTerminalsServiceUpMigrateVariant()
    {
        $this->razorxValue = 'migrate';

        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', [
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
        ]);

        $tid = $terminal['id'];

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($tid){
            if ($method == \Requests::POST)
            {
                $content = json_decode($content, true);

                $this->assertEquals('v1/terminal/submerchant', $path);

                $this->assertEquals($tid, $content[Terminal\Entity::TERMINAL_ID]);

                $this->assertEquals('10000000000000', $content[Terminal\Entity::MERCHANT_ID]);


                return $this->getDefaultTerminalServiceMerchantTerminalCreatedResponse();
            }

            if ($method == \Requests::GET)
            {
                $this->assertEquals('v2/terminal/submerchant', $path);

                $this->assertEquals($tid, $content[Terminal\Entity::TERMINAL_ID]);

                $this->assertEquals('10000000000000', $content[Terminal\Entity::MERCHANT_ID]);

                return $this->getDefaultTerminalSubmerchantFetchResponse(
                    $tid,
                    '10000000000000',
                    $content,
                    $path);

            }
        }, 2);


        $beforeCount = $this->getMerchantTerminalCount('10000000000000', $tid);

        $this->assignSubMerchant($terminal['id'], '10000000000000');

        $afterCount = $this->getMerchantTerminalCount('10000000000000', $tid);

        $this->assertEquals($beforeCount + 1, $afterCount);
    }

    public function testAddSubmerchantTerminalsServiceDownMigrateVariant()
    {
        $this->razorxValue = 'migrate';

        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', [
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
        ]);

        $tid = $terminal['id'];

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($tid){
            throw new \Requests_Exception_Transport_cURL('curl timed out', 1);
        }, 1);

        $this->expectException(\Requests_Exception_Transport_cURL::class);

        $this->expectExceptionMessage('curl timed out');

        $beforeCount = $this->getMerchantTerminalCount('10000000000000', $tid);

        $this->assignSubMerchant($terminal['id'], '10000000000000');

        $afterCount = $this->getMerchantTerminalCount('10000000000000', $tid);

        $this->assertEquals($beforeCount, $afterCount);
    }

    public function testAddSubmerchantTerminalsServiceUpBadResponseMigrateVariant()
    {
        $this->razorxValue = 'migrate';

        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', [
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
        ]);

        $tid = $terminal['id'];

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($tid){
            if ($method == \Requests::POST)
            {
                $content = json_decode($content, true);

                $this->assertEquals('v1/terminal/submerchant', $path);

                $this->assertEquals($tid, $content[Terminal\Entity::TERMINAL_ID]);

                $this->assertEquals('10000000000000', $content[Terminal\Entity::MERCHANT_ID]);

                return $this->getDefaultTerminalServiceMerchantTerminalCreatedResponse();
            }

            if ($method == \Requests::GET)
            {
                $this->assertEquals('v2/terminal/submerchant', $path);

                $this->assertEquals($tid, $content[Terminal\Entity::TERMINAL_ID]);

                $this->assertEquals('10000000000000', $content[Terminal\Entity::MERCHANT_ID]);

                $response = $this->getDefaultTerminalSubmerchantFetchResponse(strrev($tid), '10000000000000');

                return $response;
            }
        }, 2);

        $this->expectExceptionMessage('Mismatch');

        $this->expectException(IntegrationException::class);

        $beforeCount = $this->getMerchantTerminalCount('10000000000000', $terminal['id']);

        $this->assignSubMerchant($tid, '10000000000000');

        $afterCount = $this->getMerchantTerminalCount('10000000000000', $terminal['id']);

        $this->assertEquals($beforeCount, $afterCount);
    }

    public function testAddSubmerchantTerminalsServiceUpSubmerchantNotCreatedOnTerminalsServiceMigrateVariant()
    {
        $this->razorxValue = 'migrate';

        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', [
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
        ]);

        $tid = $terminal['id'];

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($tid){
            if ($method == \Requests::POST)
            {
                $content = json_decode($content, true);

                $this->assertEquals('v1/terminal/submerchant', $path);

                $this->assertEquals($tid, $content[Terminal\Entity::TERMINAL_ID]);

                $this->assertEquals('10000000000000', $content[Terminal\Entity::MERCHANT_ID]);

                return $this->getDefaultTerminalServiceMerchantTerminalCreatedResponse();
            }

            if ($method == \Requests::GET)
            {
                $this->assertEquals('v2/terminal/submerchant', $path);

                $this->assertEquals($tid, $content[Terminal\Entity::TERMINAL_ID]);

                $this->assertEquals('10000000000000', $content[Terminal\Entity::MERCHANT_ID]);

                $response = new \Requests_Response;

                $response->body = '
                data : []
                ';

                return $response;
            }
        }, 2);

        $this->expectExceptionMessage('merchant_terminal does not exist');

        $this->expectException(IntegrationException::class);

        $beforeCount = $this->getMerchantTerminalCount('10000000000000', $terminal['id']);

        $this->assignSubMerchant($tid, '10000000000000');

        $afterCount = $this->getMerchantTerminalCount('10000000000000', $terminal['id']);

        $this->assertEquals($beforeCount, $afterCount);

    }

    public function testDeleteSubmerchantTerminalsServiceUpMigrateVariant()
    {
        [$tid, $mid] = $this->testAddSubmerchantControlVariant();

        $this->razorxValue = 'migrate';

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($tid) {

            $this->assertEquals($tid, $content[Terminal\Entity::TERMINAL_ID]);

            $this->assertEquals('10000000000000', $content[Terminal\Entity::MERCHANT_ID]);

            if ($method === \Requests::DELETE)
            {
                $this->assertEquals('v1/terminal/submerchant', $path);

                return $this->getTerminalsServiceResponseForEntityDeleted();
            }

            if ($method === \Requests::GET)
            {
                $this->assertEquals('v2/terminal/submerchant', $path);


                return $this->getTerminalsServiceResponseForEntityNotFound();
            }
        }, 2);


        $beforeCount = $this->getMerchantTerminalCount('10000000000000', $tid);

        $this->deleteSubmerchant($tid, '10000000000000');

        $afterCount = $this->getMerchantTerminalCount('10000000000000', $tid);

        $this->assertEquals($beforeCount - 1, $afterCount);
    }

    public function testDeleteTerminalTerminalsServiceDownMigrateVariant()
    {
        [$tid, $mid] = $this->testAddSubmerchantControlVariant();

        $this->razorxValue = 'migrate';

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($tid) {

            $this->assertEquals($tid, $content[Terminal\Entity::TERMINAL_ID]);

            $this->assertEquals('10000000000000', $content[Terminal\Entity::MERCHANT_ID]);

            throw new \Requests_Exception_Transport_cURL('curl timed out', 1);
        }, 1);

        $this->expectException(\Requests_Exception_Transport_cURL::class);

        $this->expectExceptionMessage('curl timed out');

        $beforeCount = $this->getMerchantTerminalCount('10000000000000', $tid);

        $this->deleteSubmerchant($tid, '10000000000000');

        $afterCount = $this->getMerchantTerminalCount('10000000000000', $tid);

        $this->assertEquals($beforeCount, $afterCount);
    }

    public function testDeleteTerminalTerminalsServiceUpBadResponseMigrateVariant()
    {
        [$tid, $mid] = $this->testAddSubmerchantControlVariant();

        $this->razorxValue = 'migrate';

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($tid) {

            $this->assertEquals($tid, $content[Terminal\Entity::TERMINAL_ID]);

            $this->assertEquals('10000000000000', $content[Terminal\Entity::MERCHANT_ID]);

            if ($method === \Requests::DELETE) {

                $this->assertEquals('v1/terminal/submerchant', $path);

                return $this->getTerminalsServiceResponseForEntityDeleted();
            }

            if ($method == \Requests::GET) {
                $this->assertEquals('v2/terminal/submerchant', $path);

                $this->assertEquals($tid, $content[Terminal\Entity::TERMINAL_ID]);

                $this->assertEquals('10000000000000', $content[Terminal\Entity::MERCHANT_ID]);

                $response = $this->getDefaultTerminalSubmerchantFetchResponse($tid, '10000000000000');

                return $response;
            }
        });

        $this->expectExceptionMessage('delete failed on terminals service side');

        $this->expectException(IntegrationException::class);

        $beforeCount = $this->getMerchantTerminalCount('10000000000000', $tid);

        $this->deleteSubmerchant($tid, '10000000000000');

        $afterCount = $this->getMerchantTerminalCount('10000000000000', $tid);

        $this->assertEquals($beforeCount, $afterCount);
    }

    public function testDeleteSubmerchantControlVariant()
    {
        $this->razorxValue = 'control';

        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', [
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
        ]);

        $this->assignSubMerchant($terminal['id'], '10000000000000');


        $this->mockTerminalsServiceSendRequest(null, 0);

        $beforeCount = $this->getMerchantTerminalCount('10000000000000', $terminal['id']);

        $this->deleteSubmerchant($terminal['id'], '10000000000000');

        $afterCount = $this->getMerchantTerminalCount('10000000000000', $terminal['id']);

        $this->assertEquals($beforeCount - 1, $afterCount);
    }

    // these are tests for asserting behavior for admin_fetch_terminal_by_id route
    // in this route, we fetch terminal from API database
    // we also fetch the same terminal from Terminals Service and compare for equality
    // based on success/failure, we push metrics with dimensions.

    public function testAdminFetchTerminalByIdTerminalServiceValidResponse()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', [
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
        ]);

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($terminal) {

            $this->assertEquals("", $content);

            $this->assertEquals(Requests::GET, $method);

            $this->assertStringEndsWith($terminal['id'], $path);

            return $this->getDefaultTerminalServiceMerchantTerminalCreatedResponse();

        }, 1);

        $expected = [
            'route'         => 'admin_fetch_terminal_by_id',
            'message'       => 0,
            'terminal_id'   => $terminal['id'],
        ];

        $this->createMetricsMock()
             ->expects($this->at(4))
             ->method('count')
             ->with(Terminal\Metric::TERMINAL_FETCH_BY_ID_COMPARISON_SUCCESS, 1, $expected);

        $url = '/admin/terminal/' . $terminal['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAdminFetchTerminalByIdTerminalServiceInvalidResponse()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', [
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
        ]);

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($terminal) {

            $this->assertEquals("", $content);

            $this->assertEquals(Requests::GET, $method);

            $this->assertStringEndsWith($terminal['id'], $path);

            $data = $this->getTerminalToArrayPassword($terminal['id']);

            $data['gateway_terminal_password'] = strrev($data['gateway_terminal_password']);

            return $this->getDefaultTerminalServiceResponse($data);

        }, 1);

        $expected = [
            'route'         => 'admin_fetch_terminal_by_id',
            'message'       => 'field mismatch',
            'terminal_id'   => $terminal['id'],
        ];

        $this->createMetricsMock()
            ->expects($this->at(1))
            ->method('count')
            ->with(Terminal\Metric::TERMINAL_FETCH_BY_ID_COMPARISON_FAILURE, 1, $expected);



        $url = '/admin/terminal/' . $terminal['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

}
