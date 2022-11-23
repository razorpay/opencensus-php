<?php

namespace Unit\Models\Merchant\Asv\AsvClient;

use Config;
use DG\BypassFinals;
use Razorpay\Trace\Logger as Trace;
use Google\ApiCore\ValidationException;
use RZP\Exception\IntegrationException;
use Rzp\Accounts\Account\V1 as accountV1;
use \RZP\Tests\Functional\TestCase as Testcase;
use RZP\Models\Merchant\Acs\AsvClient\SaveApiAsvClient;

class SaveApiAsvClientTest extends TestCase
{
    function setUp(): void
    {
        parent::setUp();
        BypassFinals::enable();
        $asvConfig = [
            'host' => 'https://acs-web.razorpay.com',
            'user' => 'dummy',
            'password' => 'dummy',
            'asv_save_api_route_http_timeout_sec' => 2
        ];
        Config::set('applications', ['acs' => $asvConfig]);
    }

    function tearDown(): void
    {
        parent::tearDown();
    }

    function testSave()
    {
        $merchant = [
            "id" => "BOSbmCo5hFPvOa",
            "name" => "sumeeti",
            "email" => "sumeeti+1@razorpay.com"];

        #T1 starts - Success
        $traceMock = $this->createTraceMock(['count', 'info', 'histogram']);
        $traceMock->expects($this->exactly(2))->method('info');
        $traceMock->expects($this->exactly(2))->method('count');
        $traceMock->expects($this->exactly(1))->method('histogram');

        $saveApiRequest = new accountV1\SaveAccountEntityRequest();
        $saveApiRequest->setAccountId("10000000000000");
        $saveApiRequest->setEntityName(accountV1\ENTITY_NAME::merchant);
        $entityValueProtoStruct = get_Protobuf_Struct($merchant);
        $saveApiRequest->setEntityValue($entityValueProtoStruct);

        $expectedResponse = new accountV1\SaveAccountEntityResponse(['is_saved' => true]);
        $mockSaveAsvAPIClient = $this->createMock(accountV1\SaveApiClient::class);
        $mockSaveAsvAPIClient->method('Save')->willReturn($expectedResponse);

        $saveApiAsvClient = new SaveApiAsvClient($mockSaveAsvAPIClient);
        $actualResponse = $saveApiAsvClient->SaveEntity('10000000000000', 'merchant', $merchant);

        $this->assertEquals($actualResponse->serializeToJsonString(), $expectedResponse->serializeToJsonString(), 'testSaveMerchantSuccess');
        #T1 ends

        #T2 starts - Failure - Unsupported Entity
        $traceMock = $this->createTraceMock(['count', 'info', 'histogram', 'traceException']);
        $traceMock->expects($this->exactly(1))->method('info');
        $traceMock->expects($this->never())->method('histogram');
        $traceMock->expects($this->never())->method('count');
        $traceMock->expects($this->never())->method('traceException');

        $expectedError = new ValidationException("entity name payment is not supported");
        $mockSaveAsvAPIClient = $this->createMock(accountv1\SaveApiClient::class);
        $mockSaveAsvAPIClient->expects($this->never())->method('Save');

        $saveApiAsvClient = new SaveApiAsvClient($mockSaveAsvAPIClient);

        try {
            $saveApiAsvClient->SaveEntity('10000000000000', 'payment', ['id' => '10000000000000']);
            $this->assertFalse(true, "testSaveMerchantFailureEntityNotSupported");
        } catch (ValidationException $e) {
            $this->assertEquals($e->getCode(), $expectedError->getCode(), "testSaveMerchantFailureEntityNotSupported");
            $this->assertEquals($e->getMessage(), $expectedError->getMessage(), "testSaveMerchantFailureEntityNotSupported");
        }
        #T2 ends


        #T3 starts - Failure - server error
        $traceMock = $this->createTraceMock(['count', 'info', 'histogram', 'traceException']);
        $traceMock->expects($this->exactly(1))->method('info');
        $traceMock->expects($this->exactly(1))->method('histogram');
        $traceMock->expects($this->exactly(2))->method('count');
        $traceMock->expects($this->exactly(1))->method('traceException');

        $mockError = new accountV1\TwirpError('internal', "account can't be saved due to some unexpected error");
        $expectedError = new IntegrationException("account can't be saved due to some unexpected error");
        $mockSaveAsvAPIClient = $this->createMock(accountv1\SaveApiClient::class);
        $mockSaveAsvAPIClient->method('Save')->will($this->throwException($mockError));

        $saveApiAsvClient = new SaveApiAsvClient($mockSaveAsvAPIClient);

        try {
            $saveApiAsvClient->SaveEntity('10000000000000', 'merchant', $merchant);
            $this->assertFalse(true, "testSaveMerchantFailureInternalError");
        } catch (IntegrationException $e) {
            $this->assertEquals($e->getCode(), $expectedError->getCode(), "testSaveMerchantFailureInternalError");
            $this->assertEquals($e->getError(), $expectedError->getError(), "testSaveMerchantFailureInternalError");
        }
        #T3 ends

    }

    function createTraceMock($methods = [])
    {
        $traceMock = $this->getMockBuilder(Trace::class)
            ->disableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();
        $this->app->instance('trace', $traceMock);
        return $traceMock;
    }
}
