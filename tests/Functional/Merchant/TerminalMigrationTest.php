<?php

namespace RZP\Tests\Functional\Merchant;


use RZP\Constants\Table;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Terminal;
use RZP\Constants\Entity;
use RZP\Http\Request\Requests;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\Helpers\MocksMetricTrait;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Encryption\Encrypter;
use RZP\Exception\IntegrationException;
use Symfony\Component\HttpFoundation\Response;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Feature\Constants as FeatureConstants;


class TerminalMigrationTest extends TestCase
{
    use PaymentTrait;
    use TestsMetrics;
    use TerminalTrait;
    use MocksMetricTrait;

    protected $razorxValue = RazorXClient::DEFAULT_CASE;

    protected $merchant;

    protected $terminalsServiceMock;

    protected $terminalRepository;

    //TODO add guide to tests

    protected function setUp(): void
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

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        $this->merchant = $this->fixtures->create('merchant');


        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);
    }




    protected function getTerminalsServiceResponseForEntityNotFound()
    {
        $response = new \WpOrg\Requests\Response;

        $response->body = '
   {
    "data": null,
    "error": {
        "internal_error_code": "BAD_REQUEST_ERROR",
        "gateway_error_code": "",
        "gateway_error_description": "",
        "description": "Terminal doesn\'t exist with this Id"
    }
}';
        $response->status_code = Response::HTTP_BAD_REQUEST;

        return $response;
    }

    protected function getTerminalsServiceResponseForEntityDeleted()
    {
        $response = new \WpOrg\Requests\Response;

        $response->body = '{"data": null}';

        return $response;
    }

    protected function getDefaultTerminalServiceMerchantTerminalCreatedResponse()
    {
        return $this->getDefaultTerminalServiceResponse();
    }
    protected function getDefaultTerminalSubmerchantFetchResponse(string $terminalId, string $merchantId)
    {


        $response = new \WpOrg\Requests\Response;

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

    // the below cases are to ensure sanity when creating a terminal via internal auth
    public function testAssignTerminalInternalAuthMigrateVariant()
    {
        $this->app['config']->set('applications.terminals_service.sync', true);

        $this->mockTerminalsServiceSendRequest(function() {
            return $this->getDefaultTerminalServiceResponse();
        });

        $this->razorxValue = 'migrate';

        $url = '/merchants/'. $this->merchant->getKey(). '/terminals/internal';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $beforeCount = DB::table('terminals')->count();

        $this->ba->terminalsAuth();

        $response = $this->startTest();

        $terminalEntity = $this->terminalRepository->findOrFail($response['id']);

        $this->assertEquals(Terminal\SyncStatus::SYNC_SUCCESS, $terminalEntity->getSyncStatus());

        $afterCount = Db::table('terminals')->count();

        $this->assertEquals($beforeCount + 1, $afterCount);
    }

    public function testRazorflowTerminalFetchByIdInternalAuth()
    {
        $terminal = $this->fixtures->create('terminal');

//        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($terminal) {
//
//            $this->assertEquals("", $content);
//
//            $this->assertEquals(Requests::GET, $method);
//
//            $this->assertStringEndsWith('/admin/terminals/'.$terminal->getId(), $path);
//
//            $data = $this->terminalRepository->findOrFail($terminal['id'])->toArrayWithPassword();
//
//            $data['id'] = 'term_'.$data['id'];
//
//            return $this->getDefaultTerminalServiceResponse($data);
//
//        }, 1);
//
//        $this->razorxValue = 'proxy';
//
//        $mock = $this->createMetricsMock();
//
//        $expected = [
//            'route'         => 'razorflow_admin_fetch_terminal_by_id',
//            'message'       => null,
//            'terminal_id'   => 'term_'.$terminal['id'],
//
//        ];
//
//        $mock->expects($this->at(4))
//            ->method('count')
//            ->with(Terminal\Metric::TERMINAL_FETCH_BY_ID_COMPARISON_SUCCESS, 1, $expected);

        $url = '/rf/admin/terminal/'. $terminal->getId();

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->razorflowAuth();

        $this->startTest();
    }

    public function testRazorflowMultipleTerminalFetchInternalAuth()
    {
        $input = [
            'gateway_merchant_id' => 'testGatewayMerchantId',
            'enabled' => 1,
        ];

//        $terminal = $this->fixtures->create('terminal', $input);

//        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($terminal) {
//
//            $this->assertEquals("", $content);
//
//            $this->assertEquals(Requests::GET, $method);
//
//            $this->assertStringEndsWith('/admin/terminals/?gateway_merchant_id=testGatewayMerchantId&enabled=1&', $path);
//
//            $data = $this->terminalRepository->findOrFail($terminal['id'])->toArrayWithPassword();
//
//            $data['id'] = 'term_'.$data['id'];
//
//            $data['entity'] = 'terminal';
//
//            $body = json_encode(['data' => [$data]]);
//
//            $response = new \WpOrg\Requests\Response;
//
//            $response->body = $body;
//
//            return $response;
//
//        }, 1);

//        $this->razorxValue = 'proxy';

        $url = '/rf/admin/terminal';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->testData[__FUNCTION__]['request']['content'] = $input;

        $this->ba->razorflowAuth();

        $this->startTest();
    }

    // terminal is created via this route in fulcrum onboarding, then its synced to terminals service
    public function testAssignTerminalInternalAuthMigrateVariantFulcrum()
    {
        $this->app['config']->set('applications.terminals_service.sync', true);

        $this->mockTerminalsServiceSendRequest(function() {
            return $this->getDefaultTerminalServiceResponse();
        });


        $this->razorxValue = 'migrate';

        $url = '/merchants/'. $this->merchant->getKey(). '/terminals/internal';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $beforeCount = DB::table('terminals')->count();

        $this->ba->terminalsAuth();

        $response = $this->startTest();

        $terminalEntity = $this->terminalRepository->findOrFail($response['id']);

        $this->assertEquals(Terminal\SyncStatus::SYNC_SUCCESS, $terminalEntity->getSyncStatus());
        $this->assertEquals(1, $terminalEntity->getMode());

        $afterCount = Db::table('terminals')->count();

        $this->assertEquals($beforeCount + 1, $afterCount);
    }

     // terminal is created via this route in paysecure axis onboarding, TS calls this route then its synced back to terminals service
     public function testAssignTerminalInternalAuthMigrateVariantPaysecure()
     {
         $this->app['config']->set('applications.terminals_service.sync', true);

        $this->mockTerminalsServiceSendRequest(function() {
            return $this->getDefaultTerminalServiceResponse();
        });


        $this->razorxValue = 'migrate';

        $url = '/merchants/'. $this->merchant->getKey(). '/terminals/internal';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $beforeCount = DB::table('terminals')->count();

        $this->ba->terminalsAuth();

        $response = $this->startTest();

        $terminalEntity = $this->terminalRepository->findOrFail($response['id']);

        $this->assertEquals(Terminal\SyncStatus::SYNC_SUCCESS, $terminalEntity->getSyncStatus());

        $afterCount = Db::table('terminals')->count();

        $this->assertEquals($beforeCount + 1, $afterCount);
    }

    public function testAssignTerminalInternalAuthMissingId()
    {
        $url = '/merchants/'. $this->merchant->getKey(). '/terminals/internal';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->terminalsAuth();

        $this->startTest();
    }

    public function testTerminalCompareFunction()
    {
        $terminal = $this->fixtures->create('terminal:direct_hitachi_terminal', ["international"=> false]);

        $terminalArray = $terminal->toArray();

        $terminalArray['enabled'] = "true";
        $terminalArray['status'] = "activated";

        $newTerminaEntity = Terminal\Service::getEntityFromTerminalServiceResponse($terminalArray);

        $isEqual = Terminal\Service::compareTerminalEntity($terminal->reload(), $newTerminaEntity);

        $this->assertTrue($isEqual);
    }

    public function enableRazorxMockOn()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
        ->setConstructorArgs([$this->app])
        ->setMethods(['getTreatment'])
        ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
        ->will($this->returnCallback(
            function ($actionId, $feature, $mode)
            {
                return 'on';
            }) );
    }

    public function testCreateTokenisationTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->mockTerminalsServiceSendRequest(function($a, $b, $c) {

            return $this->getTokenisedTerminalServiceResponse($a, $b, $c);

        }, 3);

        $this->startTest();

        $this->testData[__FUNCTION__]['request']['content']['gateway'] = 'tokenisation_mastercard';

        $this->testData[__FUNCTION__]['response']['content']['gateway'] = 'tokenisation_mastercard';

        $this->startTest();

        $this->testData[__FUNCTION__]['request']['content']['gateway'] = 'tokenisation_rupay';

        $this->testData[__FUNCTION__]['response']['content']['gateway'] = 'tokenisation_rupay';

        $this->startTest();

        // aassert terminals are not present on api
        $terminal = $this->terminalRepository->findByGatewayMerchantId("tokenisation_rupay", "100000Razorpay");

        $this->assertNull($terminal);
    }

    public function testGetEntityFromTerminalServiceResponseShouldUseOrgKeyForEncryption()
    {
        $this->fixtures->org->createAxisOrg();
        // Enabling razorx mock on so that BYOK encryption of attributes when we get terminal from TS also gets tested in this test only.
        // Basically to test that buildFromTerminalServiceResponse don't cause issues
        $this->enableRazorxMockOn();

        $terminal = $this->fixtures->create('terminal:direct_hitachi_terminal', ["international"=> false]);

        $terminalArray = $terminal->toArray();

        $terminalArray['enabled'] = "true";
        $terminalArray['status'] = "activated";

        $terminalArray['org_id'] = MerchantEntity::AXIS_ORG_ID; // axis orgId
        $terminalArray['gateway_terminal_password'] = 'password1234';

        $newTerminaEntity = Terminal\Service::getEntityFromTerminalServiceResponse($terminalArray);

        $encryptedPassword = $newTerminaEntity->getRawOriginal()['gateway_terminal_password'];

        // assert that password got encrypted using axis key
        $orgKey = '5dlTd5lQhN56CkSrnyrRBtRMsXS9exWS'; // ENCRYPTION_KEY_AXIS
        $newEncrypter = new Encrypter($orgKey, 'AES-256-CBC');
        $decryptedSecret = $newEncrypter->decrypt($encryptedPassword, true);
        $this->assertEquals('password1234', $decryptedSecret);
    }


    public function testPaytmTerminalCompareFunction()
    {
        $terminal = $this->fixtures->create('terminal:shared_paytm_terminal', ["international"=> false, "mode"=>3]);

        $terminalArray = $terminal->toArray();

        $terminalArray['enabled'] = "true";
        $terminalArray['status'] = "activated";
        $terminalArray['mode'] = 3;
        $terminalArray['type'] = ["non_recurring"];

        $newTerminaEntity = Terminal\Service::getEntityFromTerminalServiceResponse($terminalArray);

        $isEqual = Terminal\Service::compareTerminalEntity($terminal->reload(), $newTerminaEntity);

        $this->assertTrue($isEqual);
    }

    public function testTerminalCollectionCompareFunction()
    {
        $merchantIds = ['10000000000000'];

        $terminals = $this->terminalRepository->getByTypeAndMerchantIds(Terminal\Type::NON_RECURRING, $merchantIds);

        $arr = $terminals->toArray();

        $newCollection = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($arr);

        $isEqual = Terminal\Service::compareTerminalCollection($terminals, $newCollection);

        $this->assertTrue($isEqual);
    }

    public function testTerminalCollectionCompareFunctionWithCompareMethod()
    {
        $merchantIds = ['10000000000000'];

        $terminals = $this->terminalRepository->getByTypeAndMerchantIds(Terminal\Type::NON_RECURRING, $merchantIds);

        $arr = $terminals->toArray();

        $newCollection = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($arr);

        $compareMethods = ["getId", "getMerchantId", "getGatewayMerchantId", "getGatewayMerchantId2"];

        $isEqual = Terminal\Service::compareTerminalCollection($terminals, $newCollection, $compareMethods);

        $this->assertTrue($isEqual);
    }

    public function testTerminalParamToRequestConversion()
    {
        $param = [];

        $param["merchant_id"]          = "10000000000000";
        $param["gateway"]              = "hitachi";
        $param["gateway_acquirer"]     = "ratn";
        $param["procurer"]             = "razorpay";
        $param["gateway_merchant_id"]  = "test1";
        $param["gateway_merchant_id2"] = "test2";
        $param["visa_mpan"]            = "visa_test";
        $param["mc_mpan"]              = "mc_test";
        $param["vpa"]                  = null;

        $req = Terminal\Service::getTerminalServiceRequestFromParam($param);

        $this->assertEquals(["10000000000000"], $req["merchant_ids"]);
        $this->assertEquals("razorpay", $req["procurer"]);
        $this->assertEquals("ratn", $req["gateway_acquirer"]);
        $this->assertEquals("hitachi", $req["gateway"]);

        $identifiers = $req["identifiers"];
        $this->assertEquals("test1", $identifiers["gateway_merchant_id"]);
        $this->assertEquals("test2", $identifiers["gateway_merchant_id2"]);
        $this->assertEquals(null, $identifiers["vpa"]);

        $mpans = $identifiers["mpans"];
        $this->assertEquals("visa_test", $mpans["visa_mpan"]);
        $this->assertEquals("mc_test", $mpans["mc_mpan"]);
    }

    public function testTerminalCollectionCompareFunctionFailed()
    {
        $merchantIds = ['10000000000000'];

        $terminals = $this->terminalRepository->getByTypeAndMerchantIds(Terminal\Type::NON_RECURRING, $merchantIds);

        $arr = $terminals->toArray();

        $arr[0]["id"] = "12345678901234";

        $newCollection = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($arr);

        $isEqual = Terminal\Service::compareTerminalCollection($terminals, $newCollection);

        $this->assertFalse($isEqual);
    }

    public function testAssignTerminalInternalAuthExistingId()
    {
        $terminal = $this->fixtures->create('terminal:shared_netbanking_bob_terminal');

        $url = '/merchants/'. $this->merchant->getKey(). '/terminals/internal';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->testData[__FUNCTION__]['request']['content']['id'] = $terminal['id'];

        $this->ba->terminalsAuth();

        $this->expectException('Illuminate\Database\QueryException');

        $this->startTest();
    }

    // the below cases tests migration functionality when a new terminal is created
    public function testAssignTerminalTerminalServiceUpMigrateTerminalVariant()
    {
        $this->app['config']->set('applications.terminals_service.sync', true);

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
        $this->app['config']->set('applications.terminals_service.sync', true);

        $this->mockTerminalsServiceSendRequest(function () {
            throw new \WpOrg\Requests\Exception\Transport\Curl('curl timed out', 1);
        }, 1);

        $this->razorxValue = 'migrate';

        $url = '/merchants/'. $this->merchant->getKey(). '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->expectException(\WpOrg\Requests\Exception\Transport\Curl::class);

        $this->expectExceptionMessage('curl timed out');

        $beforeCount = DB::table('terminals')->count();

        $this->startTest();

        $afterCount = Db::table('terminals')->count();

        $this->assertEquals($beforeCount, $afterCount);
    }

    public function testAssignTerminalServiceSuccessResponseBadValuesMigrateTerminalVariant()
    {
        $this->app['config']->set('applications.terminals_service.sync', true);

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
        $this->app['config']->set('applications.terminals_service.sync', true);

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
        $this->app['config']->set('applications.terminals_service.sync', true);

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
        $this->app['config']->set('applications.terminals_service.sync', true);

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
            throw new \WpOrg\Requests\Exception\Transport\Curl('curl timed out', 1);
        }, 1);

        $this->expectException(\WpOrg\Requests\Exception\Transport\Curl::class);

        $this->expectExceptionMessage('timed out');

        $this->startTest();

        $terminalEntity = $this->terminalRepository->findOrFail($tid);

        $this->assertTrue($terminal->isEnabled());


        $this->assertEquals(Terminal\SyncStatus::SYNC_FAILED, $terminalEntity->getSyncStatus());
    }

    public function testUpdateTerminalServiceSuccessResponseBadValuesMigrateTerminalVariant()
    {
        $this->app['config']->set('applications.terminals_service.sync', true);

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
        $this->app['config']->set('applications.terminals_service.sync', true);

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

        $this->app['config']->set('applications.terminals_service.sync', true);

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($tid) {
            $response = new  \WpOrg\Requests\Response;

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

        $this->assertEquals($beforeCount, $afterCount);

    }

    public function testDeleteTerminalNoPaymentTerminalsServiceUpTerminalDoesntExistOnTerminalsService()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', ['used' => true, 'enabled' => '1']);

        $tid = $terminal['id'];

        $url = '/terminals/'.$tid;

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->razorxValue = 'migrate';

        $this->app['config']->set('applications.terminals_service.sync', true);

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($tid) {
            $response = new  \WpOrg\Requests\Response;

            $this->assertStringEndsWith('/' . $tid, $path);

            if ($method == \Requests::DELETE)
            {
                $response = new \WpOrg\Requests\Response;

                $response->status_code = Response::HTTP_BAD_REQUEST;

                $response->body = '
                    {
                        "data": null,
                        "error": {
                            "internal_error_code": "BAD_REQUEST_ERROR",
                            "gateway_error_code": "",
                            "gateway_error_description": "",
                            "description": "Terminal doesn\'t exist with this Id"
                        }
                    }
                    ';

                $data = [
                    'response'     => json_decode($response->body, true),
                    'status_code'  => 400,
                ];

                $errorDescription = "Terminal doesn't exist with this Id";

                throw new BadRequestException(ErrorCode::BAD_REQUEST_TERMINALS_SERVICE_ERROR, null, $data, $errorDescription);

            }

            if ($method == \Requests::GET)
            {
                $data = [
                'response'     => json_decode($response->body, true),
                'status_code'  => 400,
                ];

                $errorDescription = "Terminal doesn't exist with this Id";

                throw new BadRequestException(ErrorCode::BAD_REQUEST_TERMINALS_SERVICE_ERROR, null, $data, $errorDescription);
            }

        }, 2);

        $beforeCount = DB::table('terminals')->count();

        $this->startTest();

        $afterCount = DB::table('terminals')->count();

        $this->assertEquals($beforeCount, $afterCount);
    }

    public function testDeleteTerminalNoPaymentTerminalsServiceDownMigrateVariant()
    {
        $this->app['config']->set('applications.terminals_service.sync', true);

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
            throw new \WpOrg\Requests\Exception\Transport\Curl('curl timed out', 1);
        }, 1);

        $this->expectException(\WpOrg\Requests\Exception\Transport\Curl::class);

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
        $this->app['config']->set('applications.terminals_service.sync', true);

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

        $this->assertEquals($beforeCount, $afterCount); // assert that terminal should be soft deleted
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
            if ($method === \Requests::POST)
            {
                return $this->getHitachiOnboardResponseAndCreate("1234");
            }
        }, 3);

        $tid = $this->makePaymentAndGetTerminalId();

        $terminal = $this->terminalRepository->findOrFail($tid);

        $terminal->setSyncStatus(Terminal\SyncStatus::NOT_SYNCED);

        $terminal->saveOrFail();

        $this->razorxValue = 'migrate';

        $this->app['config']->set('applications.terminals_service.sync', true);

        $url = '/terminals/'.$tid;

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $beforeCount = DB::table('terminals')->count();

        $this->startTest();

        $afterCount = DB::table('terminals')->count();

        $this->assertEquals($beforeCount, $afterCount); // assert that terminal should be soft deleted // assert that terminal should be soft deleted

        $terminalEntity = DB::table('terminals')->where('id', '=', $tid)->first(); // stdClass object

        $this->assertNotNull($terminalEntity->deleted_at);

        $this->assertEquals(Terminal\SyncStatus::getValueForSyncStatusString(Terminal\SyncStatus::SYNC_SUCCESS),
            $terminalEntity->sync_status);
    }

    public function testDeleteTerminalWithPaymentTerminalsServiceDownMigrateVariant()
    {
        $this->app['config']->set('applications.terminals_service.sync', true);

        $tid = $this->makePaymentAndGetTerminalId();

        $terminal = $this->terminalRepository->findOrFail($tid);

        $terminal->setSyncStatus(Terminal\SyncStatus::NOT_SYNCED);

        $terminal->saveOrFail();

        $url = '/terminals/'.$tid;

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->razorxValue = 'migrate';

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) {
            throw new \WpOrg\Requests\Exception\Transport\Curl('curl timed out', 1);
        }, 1);

        $this->expectException(\WpOrg\Requests\Exception\Transport\Curl::class);

        $this->expectExceptionMessage('curl');

        $beforeCount = DB::table('terminals')->count();

        $this->startTest();

        $afterCount = DB::table('terminals')->count();

        $this->assertEquals($beforeCount, $afterCount); // assert that terminal should be soft deleted

        $terminal = $this->terminalRepository->findOrFail($tid);

        // at the start of test, we set sync status of terminal to not_synced.
        // we are asserting that the value hasnt changed.
        $this->assertEquals(Terminal\SyncStatus::NOT_SYNCED, $terminal->getSyncStatus());

        $this->assertNotNull($terminal->getDeletedAt());
    }

    public function testDeleteTerminalWithPaymentTerminalsServiceUpBadResponseMigrateVariant()
    {
        $this->app['config']->set('applications.terminals_service.sync', true);

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
        $this->mockTerminalsServiceSendRequest(null, 1);

        // hitachi terminal needs to be created, otherwise one request will go for that to ts for creation
        $this->fixtures->create('terminal:direct_hitachi_terminal');

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

        $this->assertEquals(Terminal\SyncStatus::getValueForSyncStatusString(Terminal\SyncStatus::NOT_SYNCED), $terminalEntity->sync_status);
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
        $this->app['config']->set('applications.terminals_service.sync', true);

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
        $this->app['config']->set('applications.terminals_service.sync', true);

        $this->razorxValue = 'migrate';

        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', [
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
        ]);

        $tid = $terminal['id'];

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($tid){
            throw new \WpOrg\Requests\Exception\Transport\Curl('curl timed out', 1);
        }, 1);

        $this->expectException(\WpOrg\Requests\Exception\Transport\Curl::class);

        $this->expectExceptionMessage('curl timed out');

        $beforeCount = $this->getMerchantTerminalCount('10000000000000', $tid);

        $this->assignSubMerchant($terminal['id'], '10000000000000');

        $afterCount = $this->getMerchantTerminalCount('10000000000000', $tid);

        $this->assertEquals($beforeCount, $afterCount);
    }

    public function testAddSubmerchantTerminalsServiceUpBadResponseMigrateVariant()
    {
        $this->app['config']->set('applications.terminals_service.sync', true);

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
        $this->app['config']->set('applications.terminals_service.sync', true);

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

                $response = new \WpOrg\Requests\Response;

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

    public function testAddSubmerchantTerminalsServiceUpSubmerchantAlreadyExistOnTerminalsServiceMigrateVariant()
    {
        $this->app['config']->set('applications.terminals_service.sync', true);

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

                $parsedResponse = [
                    'data'  => null,
                    'error' => [
                        "internal_error_code"  => "BAD_REQUEST_ERROR",
                        "description"          =>  "Terminal Submerchant already exist",
                    ],
                ];
                $data = [
                    'response'    => $parsedResponse,
                    'status_code' => 400,
                ];

                $description = "Terminal Submerchant already exist";

                throw new BadRequestException(ErrorCode::BAD_REQUEST_TERMINALS_SERVICE_ERROR, null, $data, $description);
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

    public function testDeleteSubmerchantTerminalsServiceUpMigrateVariant()
    {
        [$tid, $mid] = $this->testAddSubmerchantControlVariant();

        $this->razorxValue = 'migrate';

        $this->app['config']->set('applications.terminals_service.sync', true);

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

        $this->app['config']->set('applications.terminals_service.sync', true);

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($tid) {

            $this->assertEquals($tid, $content[Terminal\Entity::TERMINAL_ID]);

            $this->assertEquals('10000000000000', $content[Terminal\Entity::MERCHANT_ID]);

            throw new \WpOrg\Requests\Exception\Transport\Curl('curl timed out', 1);
        }, 1);

        $this->expectException(\WpOrg\Requests\Exception\Transport\Curl::class);

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

        $this->app['config']->set('applications.terminals_service.sync', true);

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

    public function testDeleteSubmerchantAlreadyDeletedTerminalsServiceMigrateVariant()
    {
        [$tid, $mid] = $this->testAddSubmerchantControlVariant();

        $this->razorxValue = 'migrate';

        $this->app['config']->set('applications.terminals_service.sync', true);

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($tid) {

            $this->assertEquals($tid, $content[Terminal\Entity::TERMINAL_ID]);

            $this->assertEquals('10000000000000', $content[Terminal\Entity::MERCHANT_ID]);

            if ($method === \Requests::DELETE)
            {
                $this->assertEquals('v1/terminal/submerchant', $path);

                $parsedResponse = [
                    'data' => null,
                    'error' => [
                        "internal_error_code" => "BAD_REQUEST_ERROR",
                        "description"         => "Terminal Submerchant relation doesn't exist",
                    ]
                ];
                $data = [
                    'response'    => $parsedResponse,
                    'status_code' => 400,
                ];

                $errorDescription = "Terminal Submerchant relation doesn't exist";

                throw new BadRequestException(ErrorCode::BAD_REQUEST_TERMINALS_SERVICE_ERROR, null, $data, $errorDescription);
            }

            if ($method == \Requests::GET)
            {
                $this->assertEquals('v2/terminal/submerchant', $path);

                $this->assertEquals($tid, $content[Terminal\Entity::TERMINAL_ID]);

                $this->assertEquals('10000000000000', $content[Terminal\Entity::MERCHANT_ID]);

               return $this->getTerminalsServiceResponseForEntityNotFound();
            }
        });

        $beforeCount = $this->getMerchantTerminalCount('10000000000000', $tid);

        $this->deleteSubmerchant($tid, '10000000000000');

        $afterCount = $this->getMerchantTerminalCount('10000000000000', $tid);

        $this->assertEquals($beforeCount - 1, $afterCount);
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

    public function testBulkAssignBuyPricingPlans()
    {
        $terminal = $this->fixtures->create(
            'terminal', [
            'gateway_merchant_id'        => 'testGatewayMerchantId',
            'enabled' => '1'
        ]);

        $buyPricingPlan = $this->createBuyPricingPlan();

        $url = '/buy_pricing/assign/bulk/';

        $input = [
            [
                'idempotency_key' => 'randomKey',
                'terminal_id'     => $terminal->getId(),
                'plan_name'       => $buyPricingPlan['rules'][0]['plan_name'],
            ]
        ];

        $this->testData[__FUNCTION__]['request']['url'] = $url;
        $this->testData[__FUNCTION__]['request']['content'] = $input;

        $response = $this->startTest();

        $this->assertTrue($response['items'][0]['success']);
    }

    public function testAdminFetchMultipleTerminalsProxy()
    {
        $terminal = $this->fixtures->create(
            'terminal', [
            'gateway_merchant_id'        => 'testGatewayMerchantId',
            'enabled' => '1'
        ]);
//
//        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($terminal) {
//
//            $this->assertEquals("", $content);
//
//            $this->assertEquals(Requests::GET, $method);
//
//            $this->assertStringEndsWith('gateway_merchant_id=testGatewayMerchantId&enabled=1&', $path);
//
//            $data = $this->terminalRepository->findOrFail($terminal['id'])->toArrayWithPassword();
//
//            $body = json_encode(['data' => [$data]]);
//
//            $response = new \WpOrg\Requests\Response;
//
//            $response->body = $body;
//
//            return $response;
//
//        }, 1);

//        $this->razorxValue = 'proxy';

        $url = '/admin/terminal/';

        $input = [
            'gateway_merchant_id' => 'testGatewayMerchantId',
            'enabled' => 1,
        ];

        $this->testData[__FUNCTION__]['request']['url'] = $url;
        $this->testData[__FUNCTION__]['request']['content'] = $input;

        $this->startTest();
    }

    public function testAdminFetchTerminalByIdTerminalServiceValidResponseProxy()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', [
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
        ]);

//        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($terminal) {
//
//            $this->assertEquals("", $content);
//
//            $this->assertEquals(Requests::GET, $method);
//
//            $this->assertStringEndsWith($terminal['id'], $path);
//
//            $response = new \WpOrg\Requests\Response;
//
//            $data = $this->terminalRepository->findOrFail($terminal['id'])->toArrayWithPassword();
//
//            $data['id'] = 'term_'.$data['id'];
//
//            return $this->getDefaultTerminalServiceResponse($data);
//
//        }, 1);

//        $this->razorxValue = 'proxy';

//        $mock = $this->createMetricsMock();
//
//        $expected = [
//            'route'         => 'admin_fetch_terminal_by_id',
//            'message'       => null,
//            'terminal_id'   => 'term_'.$terminal['id'],
//
//        ];
//
//        $mock->expects($this->at(4))
//            ->method('count')
//            ->with(Terminal\Metric::TERMINAL_FETCH_BY_ID_COMPARISON_SUCCESS, 1, $expected);

        $url = '/admin/terminal/' . $terminal['id'] . '/';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAdminFetchTerminalByIdForHdfcOrg()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprHdfcbToken', $org->getPublicId(), 'hdfcbank.com');

        $terminal = $this->fixtures->create(
            'terminal:shared_hdfc_terminal', [
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
            'org_id'      => $org->getId(),
        ]);

        $this->razorxValue = 'control';

        $url = '/admin/external_org/terminal/' . $terminal['id'] . '/';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

    }

    public function testAdminFetchTerminalByIdForAxisOrg()
    {
        $org = $this->fixtures->org->createAxisOrg();

        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprAxisbToken', $org->getPublicId(), 'hdfcbank.com');

        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', [
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
            'org_id'      => $org->getId(),
        ]);

        $this->razorxValue = 'control';

        $expected = [
            'route'         => 'admin_fetch_terminal_by_id',
            'message'       => null,
            'terminal_id'   => 'term_'.$terminal['id'],

        ];

        $url = '/admin/external_org/terminal/' . $terminal['id'] . '/';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $expectedResponse = $this->testData[__FUNCTION__]['response']['content'];

        $this->fixtures->create('feature', [
            'entity_id' => $org->getId(),
            'name'   => 'axis_org',
            'entity_type' => 'org',
        ]);

        $response = $this->startTest();

        $this->assertEquals($expectedResponse['id'], $response['id']);

        $this->assertEquals($expectedResponse['org_id'], $response['org_id']);

        $this->assertEquals($expectedResponse['gateway'], $response['gateway']);
    }

    public function testAdminFetchTerminalByIdForDifferentOrg()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprHdfcbToken', $org->getPublicId(), 'hdfcbank.com');

        $org = $this->fixtures->org->createAxisOrg();

        $terminal = $this->fixtures->create(
            'terminal:shared_hdfc_terminal', [
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
            'org_id'      => $org->getId(),
        ]);

        $this->razorxValue = 'control';

        $url = '/admin/external_org/terminal/' . $terminal['id'] . '/';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

    }

    public function testAdminFetchTerminalByIdForInvalidTerminal()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprHdfcbToken', $org->getPublicId(), 'hdfcbank.com');

        $this->razorxValue = 'control';

        $url = '/admin/external_org/terminal/invalidTerminalId'  . '/';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

    }
    public function testCheckEncryptedValueTerminalServiceValidResponseProxy()
    {
        $data = [
            'gateway_terminal_password' => 'testpassword',
            'gateway_terminal_password2' => 'testpassword',
            'gateway_secure_secret' => 'testsecret',
            'gateway_secure_secret2' => 'testsecret'
        ];

        $terminal = $this->fixtures->create(
            'terminal', $data);

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($terminal, $data) {

            $this->assertEquals(json_encode($data), $content);

            $this->assertEquals(Requests::POST, $method);

            $this->assertStringEndsWith('/terminals/'.$terminal['id'].'/secrets', $path);

            return $this->getTerminalServiceCheckSecretResponse();

        }, 1);

        $this->razorxValue = 'proxy';

        $url = '/terminals/' . $terminal['id'] . '/secret/';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testFetchTerminalProxy()
    {
        DB::table('terminals')->delete();

        $terminal = $this->fixtures->create(
            'terminal', ['merchant_id' => '10000000000000']);

        $this->app['basicauth']->setPartnerMerchantId('10000000000000');

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $this->ba->privateAuth();

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) {

            $data = [
                "merchant_ids" => ['10000000000000']
            ];

            $this->assertEquals(json_encode($data), $content);

            $this->assertEquals(Requests::POST, $method);

            $this->assertStringEndsWith('/public/merchants/terminals', $path);

            $data = $this->terminalRepository->getByMerchantId('10000000000000')->toArray();

            $data[0]['entity'] = 'terminal';

            $data[0]['id'] = 'term_'.$data[0]['id'];

            $body = json_encode(['data' => $data]);

            $response = new \WpOrg\Requests\Response;

            $response->body = $body;

            return $response;

        }, 1);

        $this->razorxValue = 'proxy';

        $url = '/terminals/';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditTerminalOnTerminalService()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'payu',
                'gateway_merchant_id' => '250000002',
                'gateway_secure_secret' => "1231424",
                'mode' => 3,
                'type'    => [
                    'direct_settlement_with_refund' => '1'
                ],
            ]);
        $tid = $terminal['id'];

        $data = [
            'mode' => "2",
        ];

        $this->razorxValue = 'on';

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) {

            $data = [
                "mode" => "2"
            ];

            $this->assertEquals(json_encode($data), $content);

            $this->assertEquals(Requests::PATCH, $method);

            $this->assertStringEndsWith('/terminals/AqdfGh5460opVt', $path);

            $data = $this->terminalRepository->getById('AqdfGh5460opVt')->toArray();

            $data['mode'] = '2';

            $body = json_encode(['data' => $data]);

            $response = new \WpOrg\Requests\Response;

            $response->body = $body;

            return $response;

        }, 1);

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals( "2", $content['mode']);

        $apiTerminal = $this->terminalRepository->getById('AqdfGh5460opVt')->toArray();

        $this->assertEquals( "sync_success", $apiTerminal['sync_status']);
    }

    public function testEditTerminalOnTerminalServiceBadRequest()
    {
        $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'payu',
                'gateway_merchant_id' => '250000002',
                'gateway_secure_secret' => "1231424",
                'mode' => 3,
                'type' => [
                    'direct_settlement_with_refund' => '1'
                ],
            ]);

        $this->razorxValue = 'on';

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) {

            $data = [
                "mode" => "2"
            ];

            $this->assertEquals(json_encode($data), $content);

            $this->assertEquals(Requests::PATCH, $method);

            $this->assertStringEndsWith('/terminals/AqdfGh5460opVt', $path);

            return $this->getProxyEditTerminalServiceResponseBadRequest();

        }, 1);

        $this->startTest();
    }

    public function testRestoreTerminalWithTerminalService()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'payu',
                'gateway_merchant_id' => '250000002',
                'gateway_secure_secret' => "1231424",
                'mode' => 3,
                'type' => [
                    'direct_settlement_with_refund' => '1'
                ],
            ]);

        $t = $this->deleteTerminal2('AqdfGh5460opVt');

        $this->assertNotNull($t['deleted_at']);

        $this->razorxValue = 'restore_terminal';

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) {

            return $this->getProxyRestoreTerminalServiceResponse();

        }, 1);

        $this->startTest();
    }

    public function testRestoreTerminalOnTerminalServiceBadRequest()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'payu',
                'gateway_merchant_id' => '250000002',
                'gateway_secure_secret' => "1231424",
                'mode' => 3,
                'type' => [
                    'direct_settlement_with_refund' => '1'
                ],
            ]);

        $t = $this->deleteTerminal2('AqdfGh5460opVt');

        $this->assertNotNull($t['deleted_at']);

        $this->razorxValue = 'restore_terminal';

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) {

            return $this->getProxyEditTerminalServiceResponseBadRequest();

        }, 1);

        $this->startTest();
    }

    public function testReassignTerminalWithTerminalService()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'payu',
                'gateway_merchant_id' => '250000002',
                'gateway_secure_secret' => "1231424",
                'mode' => 3,
                'type' => [
                    'direct_settlement_with_refund' => '1'
                ],
            ]);

        $this->razorxValue = 'reassign_merchant';

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) {

            return $this->getProxyReassignTerminalServiceResponse();

        }, 1);

        $this->startTest();
    }

    public function testReassignTerminalOnTerminalServiceBadRequest()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'payu',
                'gateway_merchant_id' => '250000002',
                'gateway_secure_secret' => "1231424",
                'mode' => 3,
                'type' => [
                    'direct_settlement_with_refund' => '1'
                ],
            ]);

        $this->razorxValue = 'reassign_merchant';

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) {

            return $this->getProxyEditTerminalServiceResponseBadRequest();

        }, 1);

        $this->startTest();
    }

    public function testToggleTerminalFromTerminalService()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'enabled' => true,
                'procurer' => 'razorpay',
                'gateway_terminal_password' => 'test_password',
                'gateway_acquirer' => 'hdfc',
                'status' => 'activated',
            ]);
        $tid = $terminal['id'];

        $originalTerminal = $this->terminalRepository->findOrFail($tid)->toArrayWithPassword();

        $this->razorxValue = 'on';

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($tid, $originalTerminal) {

            if ($method === Requests::GET)
            {
                $data = $originalTerminal;
                // Setting mismatches to validate no other fields were effected.
                $data["procurer"] = "merchant";
                $data["gateway_terminal_password"] = null;
                $data["gateway_acquirer"] = "ratn";
                $data["status"] = "deactivated";

                return $this->getDefaultTerminalServiceResponse($data);
            }

            $data = $originalTerminal;

            $data["enabled"] = false;

            $body = json_encode(['data' => $data]);

            $response = new \WpOrg\Requests\Response;

            $response->body = $body;

            return $response;

        }, 1);

        $url = '/terminals/'.$tid.'/toggle';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();

        $this->razorxValue = 'off';

        // Verifying fields.
        $finalTerminal = $this->terminalRepository->findOrFail($tid)->toArrayWithPassword();

        foreach ($finalTerminal as $key => $value)
        {
            if ($key === 'enabled' || $key === 'updated_at')
            {
                continue;
            }

            if ($key === 'sync_status')
            {
                $this->assertEquals("sync_success", $finalTerminal[$key]);

                continue;
            }
            $this->assertEquals($originalTerminal[$key], $finalTerminal[$key]);
        }
    }

    public function testFind()
    {
        DB::table('terminals')->delete();

        $terminal = $this->fixtures->create(
            'terminal', [
            'id'          => '1n25f6uN5S1Z5a',
            'merchant_id' => '10000000000000',
        ]);

        $terminal2 = $this->terminalRepository->find('1n25f6uN5S1Z5a');

        $this->assertEquals($terminal->getId(), $terminal2->getId());
    }

    public function testFindByGatewayAndTerminalData()
    {
        DB::table('terminals')->delete();

        $terminal = $this->fixtures->create(
            'terminal', [
            'merchant_id' => '10000000000000',
            'gateway' => 'upi_yesbank'
        ]);

        $terminal2 = $this->terminalRepository->findByGatewayAndTerminalData('upi_yesbank');

        $this->assertEquals($terminal->getId(), $terminal2->getId());
    }

    public function testFindByFpxGatewayAndTerminalData()
    {
        DB::table('terminals')->delete();

        $terminal = $this->fixtures->create(
            'terminal', [
            'merchant_id' => '10000000000000',
            'gateway' => 'fpx'
        ]);

        $terminal2 = $this->terminalRepository->findByGatewayAndTerminalData('fpx');

        // if gateway is fpx, then fpx is true
        $this->assertEquals(true, $terminal2->fpx);

        $this->assertEquals($terminal->getId(), $terminal2->getId());
    }

    public function testFindByEghlGatewayAndTerminalData()
    {
        DB::table('terminals')->delete();

        $terminal = $this->fixtures->create(
            'terminal', [
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'eghl',
            'gateway_merchant_id'       => 'EGHL00001000',
            'card'                      => 1,
            'enabled_wallets'           => ['boost'],
            'gateway_terminal_password' => 'abcd',
            'gateway_acquirer'          => 'eghl',
            'capability'                => 0,
            'type'                      => [
                'non_recurring' => '1',
            ],
            'currency'                  => ['MYR']
        ]);

        $terminal2 = $this->terminalRepository->findByGatewayAndTerminalData('eghl');

        // if wallet is present in gateway or not
        $this->assertTrue(in_array(\RZP\Models\Payment\Processor\Wallet::BOOST, $terminal2->getEnabledWallets()));

        $this->assertEquals($terminal->getId(), $terminal2->getId());

    }

    public function testSetTerminalBanksOnTerminalService()
    {
        DB::table('terminals')->delete();

        $terminal = $this->fixtures->create(
            'terminal', [
            'id'          => '1n25f6uN5S1Z5a',
            'merchant_id' => '10000000000000',
            'netbanking' => 1,
            'gateway' => 'netbanking_sbi'
        ]);

        $data = [
            'enabled' => [
                'SBBJ' => 'State Bank of Bikaner and Jaipur',
                'SBHY' => 'State Bank of Hyderabad',
            ],
            'disabled' => [
                'SBIN' => 'State Bank of India',
                'SBMY' => 'State Bank of Mysore',
                'STBP' => 'State Bank of Patiala',
                'SBTR' => 'State Bank of Travancore',
            ]
        ];

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
            ->method('getTreatment')
            ->will($this->returnCallback(function ($mid, $feature, $mode)
            {
                if ($feature === 'TERMINAL_EDIT_PROXY')
                {
                    return 'on';
                }

                return 'off';
            }));

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($terminal, $data) {

            $response = new \WpOrg\Requests\Response;

            $this->assertEquals("v1/terminals/". $terminal->getId() . "/banks", $path);

            $body = json_encode(['data' => $data]);

            $response->body = $body;

            return $response;
        }, 1);

        $this->ba->adminAuth();

        $this->testData[__FUNCTION__]['response']['content'] = $data;

        $this->startTest();
    }

    public function testSetTerminalsBanksOnTerminalService()
    {
        DB::table('terminals')->delete();

        $terminal = $this->fixtures->create(
            'terminal', [
            'id'          => '1n25f6uN5S1Z5a',
            'merchant_id' => '10000000000000',
            'netbanking' => 1,
            'gateway' => 'netbanking_sbi'
        ]);

        $data = [
            '1n25f6uN5S1Z5a'=> [
                'SBIN' => 'State Bank of India',
                'SBMY' => 'State Bank of Mysore',
                'STBP' => 'State Bank of Patiala',
                'SBTR' => 'State Bank of Travancore',
            ],
            'success' => true,
        ];

        $this->razorxValue = "on";

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($data) {

            $response = new \WpOrg\Requests\Response;

            $this->assertEquals("v1/terminals/banks", $path);

            $body = json_encode(['data' => $data]);

            $response->body = $body;

            return $response;
        }, 1);

        $this->ba->adminAuth();

        $this->testData[__FUNCTION__]['response']['content'] = $data;

        $this->startTest();
    }

    public function testSetTerminalsBanksAndSync()
    {
        DB::table('terminals')->delete();

        $terminal = $this->fixtures->create(
            'terminal', [
            'id'          => '1n25f6uN5S1Z5a',
            'merchant_id' => '10000000000000',
            'netbanking' => 1,
            'gateway' => 'netbanking_sbi'
        ]);

        $this->app['config']->set('applications.terminals_service.sync', true);

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) {

            $response = new \WpOrg\Requests\Response;

            $data = $this->terminalRepository->find('1n25f6uN5S1Z5a')->toArray();

            $body = json_encode(['data' => $data]);

            $response->body = $body;

            return $response;
        }, 2);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEnableTerminalOnTerminalService()
    {
        DB::table('terminals')->delete();

        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id'          => '1n25f6uN5S1Z5a',
                'merchant_id' => '10000000000000',
                'enabled'     => false,
                'status'      => 'deactivated',
                'gateway'     => 'worldline'
            ]);

        $this->razorxValue = 'on';

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) {

            $data = $this->terminalRepository->findOrFail('1n25f6uN5S1Z5a')->toArrayWithPassword();

            $data["enabled"] = true;

            $data["status"] = "activated";

            $body = json_encode(['data' => $data]);

            $response = new \WpOrg\Requests\Response;

            $response->body = $body;

            return $response;

        }, 1);

        $this->app['basicauth']->setPartnerMerchantId('10000000000000');

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testDisableTerminalOnTerminalService()
    {
        DB::table('terminals')->delete();

        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id'          => '1n25f6uN5S1Z5a',
                'merchant_id' => '10000000000000',
                'enabled'     => false,
                'status'      => 'activated',
                'gateway'     => 'worldline'
            ]);

        $this->razorxValue = 'on';

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) {

            $data = $this->terminalRepository->findOrFail('1n25f6uN5S1Z5a')->toArrayWithPassword();

            $data["enabled"] = false;

            $data["status"] = "deactivated";

            $body = json_encode(['data' => $data]);

            $response = new \WpOrg\Requests\Response;

            $response->body = $body;

            return $response;

        }, 1);

        $this->app['basicauth']->setPartnerMerchantId('10000000000000');

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $this->ba->privateAuth();

        $this->startTest();
    }

    // tests for fetching all terminals of merchant in admin route
    public function testFetchTerminalsAdminAuth()
    {
        $this->markTestSkipped("until terminal service response is returned");

        DB::table('terminals')->delete();

        $terminal = $this->fixtures->create(
            'terminal', [
            'id'          => '1n25f6uN5S1Z5a',
            'merchant_id' => '10000000000000',
        ]);

        $this->razorxValue = 'migrate';

        $this->app['config']->set('applications.terminals_service.sync', true);

        $terminal = $this->fixtures->create(
            'terminal', [
            'merchant_id' => '10000000000000',
            'used' => true,
            'enabled' => '1',
            'sync_status' => 'sync_success',
        ]);

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($terminal) {
            $response = new \WpOrg\Requests\Response;

            $this->assertEquals(Requests::POST, $method);

            $this->assertEquals("v1/merchants/terminals", $path);

            $expectedContent = ["merchant_ids" => ["10000000000000"], "sub_merchant"=> false, "status"=> "activated"];

            $this->assertEquals(json_encode($expectedContent), $content);

            $data = $this->terminalRepository->getByMerchantId('10000000000000')->toArray();

            $body = json_encode(['data' => $data]);

            $response->body = $body;

            return $response;
        }, 1);

        $this->ba->adminAuth();

        $mock = $this->createMetricsMock();

        $expectedSuccess1 = [
            'route'       => 'merchant_get_terminals',
            'message'     => null,
            'terminal_id' => '1n25f6uN5S1Z5a',
        ];

        $expectedSuccess2 = [
            'route'       => 'merchant_get_terminals',
            'message'     => null,
            'terminal_id' => $terminal['id'],
        ];

        $mock->expects($this->at(3))
            ->method('count')
            ->with(Terminal\Metric::TERMINAL_FETCH_BY_ID_COMPARISON_SUCCESS, 1, $expectedSuccess1);

        $mock->expects($this->at(4))
            ->method('count')
            ->with(Terminal\Metric::TERMINAL_FETCH_BY_ID_COMPARISON_SUCCESS, 1, $expectedSuccess2);

        $this->startTest();
    }

    public function testFetchTerminalsAdminAuthProxy()
    {
        DB::table('terminals')->delete();

        $terminal = $this->fixtures->create(
            'terminal', [
            'id'          => '1n25f6uN5S1Z5a',
            'merchant_id' => '10000000000000',
        ]);

        $this->razorxValue = 'proxy';

        $terminal = $this->fixtures->create(
            'terminal', [
            'merchant_id' => '10000000000000',
            'used' => true,
            'enabled' => '1',
            'sync_status' => 'sync_success',
        ]);

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($terminal) {
            $response = new \WpOrg\Requests\Response;

            $this->assertEquals(Requests::POST, $method);

            $this->assertEquals("v1/merchants/terminals", $path);

            $expectedContent = ["merchant_ids" => ["10000000000000"], "sub_merchant"=> false, "deleted" => true];

            $this->assertEquals(json_encode($expectedContent), $content);

            $data = $this->terminalRepository->getByMerchantId('10000000000000')->toArray();

            $data[0]['entity'] = 'terminal';
            $data[1]['entity'] = 'terminal';

            $body = json_encode(['data' => $data]);

            $response->body = $body;

            return $response;
        }, 1);

        $this->ba->adminAuth();

        $mock = $this->createMetricsMock();

        $expectedSuccess = [
            'route'       => 'merchant_get_terminals',
            'function'     => 'addMerchantWhereCondition'
        ];

        $mock->expects($this->at(6))
            ->method('count')
            ->with(Terminal\Metric::TERMINAL_REPO_READ, 1, $expectedSuccess);

        $this->startTest();
    }

    public function testTerminalToArryPassword()
    {
        DB::table('terminals')->delete();
        $terminal = $this->fixtures->create('terminal:shared_olamoney_terminal', ['type' => ['non_recurring' => '1', 'ivr' => '1']]);

        $terminalId = $terminal->getId();

        $this->razorxValue = 'terminal_credential_proxy';

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($terminalId)  {
            $response = new \WpOrg\Requests\Response;

            $this->assertEquals(Requests::GET, $method);

            $this->assertEquals("v2/terminals/credentials/" . $terminalId, $path);

            $data = [];
            $data['terminal'] = [];
            $data['terminal']['secrets'] = [];
            $data['terminal']['secrets']['gateway_terminal_password'] = "123456789";
            $data['terminal']['secrets']['gateway_terminal_password2'] = null;
            $data['terminal']['secrets']['gateway_secure_secret'] = "aasdfghjkl";
            $data['terminal']['secrets']['gateway_secure_secret2'] = null;
            $data['terminal']['secrets']['gateway_recon_password'] = null;

            $body = json_encode(['data' => $data]);

            $response->body = $body;

            return $response;
        }, 1);

        $secrets = $terminal->toArrayWithPassword();

        $expectedSecrets = [];
        $expectedSecrets["gateway_terminal_password"] = "123456789";
        $expectedSecrets["gateway_terminal_password2"] = null;
        $expectedSecrets["gateway_secure_secret"] = "aasdfghjkl";
        $expectedSecrets["gateway_secure_secret2"] = null;

        $this->assertEquals($expectedSecrets["gateway_terminal_password"], $secrets["gateway_terminal_password"]);
        $this->assertEquals($expectedSecrets["gateway_terminal_password2"], $secrets["gateway_terminal_password2"]);
        $this->assertEquals($expectedSecrets["gateway_secure_secret"], $secrets["gateway_secure_secret"]);
        $this->assertEquals($expectedSecrets["gateway_secure_secret2"], $secrets["gateway_secure_secret2"]);
    }

    public function testFetchTerminalBanksAdminAuthProxy()
    {
        DB::table('terminals')->delete();

        $terminal = $this->fixtures->create(
            'terminal', [
            'id'          => '1n25f6uN5S1Z5a',
            'merchant_id' => '10000000000000',
            'netbanking' => 1,
            'gateway' => 'netbanking_sbi'
        ]);

        $data = [
            'enabled' => [
                'SBBJ' => 'State Bank of Bikaner and Jaipur',
                'SBHY' => 'State Bank of Hyderabad',
                'SBIN' => 'State Bank of India',
                'SBMY' => 'State Bank of Mysore',
                'STBP' => 'State Bank of Patiala',
                'SBTR' => 'State Bank of Travancore'
            ],
            'disabled' => []
        ];

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
            ->method('getTreatment')
            ->will($this->returnCallback(function ($mid, $feature, $mode)
            {
                if ($feature === 'ROUTE_PROXY_TS_BANK_FETCH')
                {
                    return 'proxy';
                }

                return 'migrate';
            }));

        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) use ($terminal, $data) {
            $response = new \WpOrg\Requests\Response;

            $this->assertEquals(Requests::GET, $method);

            $this->assertEquals("v1/terminals/". $terminal->getId() . "/banks", $path);

            $body = json_encode(['data' => $data]);

            $response->body = $body;

            return $response;
        }, 1);

        $this->ba->adminAuth();

        $response = $this->startTest();

        $this->assertEquals($data, $response);
    }

    /* this is to assert that terminal doesnt get synced in normal payment callback
    * exceptions:
     * 1) terminal is used for first time
     * 2) terminal gets disabled
     */
    public function testPaymentCallbackNoSyncForUsedTerminal()
    {
        $this->fixtures->edit('terminal', '1n25f6uN5S1Z5a', [
            'used' => true,
        ]);

        $this->razorxValue = 'migrate';

        $this->mockTerminalsServiceSendRequest(null, 0);

       $this->fixtures->create('terminal:direct_hitachi_terminal', ["category"=>"5399"]);

        $this->doAuthPayment();
    }

    public function testSyncDeletedTerminals()
    {
        $this->ba->cronAuth();

        $this->mockTerminalsServiceSendRequest(function() {
            return $this->getSyncDeleteTerminalTerminalServiceResponse();
        }, 1);


        $this->startTest();

    }

    public function testTerminalServiceProxyDeleteTerminalSubmerchant()
    {
        $this->ba->cronAuth();

        $this->mockTerminalsServiceSendRequest(function() {
            return $this->getProxyDeleteTerminalSubmerchantTerminalServiceResponse();
        }, 1);

        $this->startTest();
    }

    public function testTerminalServiceProxyCreateGatewayCredential()
    {
        $this->ba->adminAuth();

        $this->mockTerminalsServiceSendRequest(function() {
            return $this->getProxyCreateGatewayCredentialTerminalServiceResponse();
        }, 1);

        $this->startTest();
    }

    public function testTerminalServiceProxyFetchGatewayCredential()
    {
        $this->ba->proxyAuth();

        $this->mockTerminalsServiceSendRequest(function() {
            return $this->getProxyFetchGatewayCredentialTerminalServiceResponse();
        }, 1);

        $this->startTest();
    }

    public function testTerminalServiceProxyFetchMerchantsTerminals()
    {
        $this->ba->adminAuth();

        $this->mockTerminalsServiceSendRequest(function() {
            return $this->getProxyFetchMerchantsTerminalsTerminalServiceResponse();
        }, 1);

        $this->startTest();
    }


    public function testTerminalServiceProxyCreateTerminalSubmerchant()
    {
        $this->ba->cronAuth();

        $this->mockTerminalsServiceSendRequest(function() {
            return $this->getProxyCreateTerminalSubmerchantTerminalServiceResponse();
        }, 1);

        $this->startTest();
    }

    // below two tests assert right exceptions are raised when call to
    // terminals services generates 4xx and 5xx errors on Terminals Service
    public function test4xxException()
    {
        $this->ba->cronAuth();

        $terminalsServiceMock = $this->getTerminalsServiceMock();

        $terminalsServiceMock->shouldReceive('makeRequest')
            ->andReturnUsing(function () {
                $response = new \WpOrg\Requests\Response();
                $response->body = '
                    {
                        "error": {
                            "description": "foo"
                        }
                    }';
                $response->status_code = 400;
                return $response;
            });

        $this->startTest();
    }

    public function test5xxException()
    {
        $this->ba->cronAuth();

        $terminalsServiceMock = $this->getTerminalsServiceMock();

        $terminalsServiceMock->shouldReceive('makeRequest')
            ->andReturnUsing(function () {
                $response = new \WpOrg\Requests\Response();
                $response->status_code = 500;
                return $response;
            });

        $this->startTest();
    }
}
