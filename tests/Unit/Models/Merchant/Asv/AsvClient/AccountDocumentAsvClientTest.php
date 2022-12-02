<?php

namespace Unit\Models\Merchant\Asv\AsvClient;

use Config;
use DG\BypassFinals;
use Razorpay\Trace\Logger as Trace;
use Rzp\Accounts\Account\V1\FetchMerchantDocumentsResponse;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\Acs\AsvClient\AccountDocumentAsvClient;
use \RZP\Tests\Functional\TestCase as Testcase;
use Rzp\Accounts\Account\V1 as accountDocumentV1;

class AccountDocumentAsvClientTest extends TestCase
{
    function setUp(): void
    {
        parent::setUp();
        BypassFinals::enable();
        $asvConfig = [
            'host' => 'https://acs-web.razorpay.com',
            'user' => 'dummy',
            'password' => 'dummy',
            'document_delete_route_http_timeout_sec' => 2,
            'asv_fetch_route_http_timeout_sec' => 2
        ];
        Config::set('applications', ['acs' => $asvConfig]);
    }

    function tearDown(): void
    {
        parent::tearDown();
    }

    function testDeleteAccountDocument()
    {
        #T1 starts - Success
        $traceMock = $this->createTraceMock(['count', 'info', 'histogram']);
        $traceMock->expects($this->exactly(2))->method('info');
        $traceMock->expects($this->exactly(2))->method('count');
        $traceMock->expects($this->exactly(1))->method('histogram');

        $expectedResponse = new accountDocumentV1\DeleteDocumentResponse(['deleted' => 'true']);
        $mockAccountDocumentAsvAPIClient = $this->createMock(accountDocumentV1\DocumentAPIClient::class);
        $mockAccountDocumentAsvAPIClient->method('Delete')->willReturn($expectedResponse);

        $accountDocumentAsvClient = new AccountDocumentAsvClient($mockAccountDocumentAsvAPIClient);
        $actualResponse = $accountDocumentAsvClient->DeleteAccountDocument('10000000000000');

        $this->assertEquals($actualResponse->serializeToJsonString(), $expectedResponse->serializeToJsonString(), 'testDeleteAccountDocumentSuccess');
        #T1 ends

        #T2 starts - Failure - Id doesn't exist
        $traceMock = $this->createTraceMock(['count', 'info', 'histogram', 'traceException']);
        $traceMock->expects($this->exactly(1))->method('info');
        $traceMock->expects($this->exactly(1))->method('histogram');
        $traceMock->expects($this->exactly(2))->method('count');
        $traceMock->expects($this->exactly(1))->method('traceException');

        $mockError = new accountDocumentV1\TwirpError('invalid_argument', "id doesn't exists hence can't be deleted");
        $expectedError = new IntegrationException('Could not receive proper response from Account service');
        $mockAccountDocumentAsvAPIClient = $this->createMock(accountDocumentv1\DocumentAPIClient::class);
        $mockAccountDocumentAsvAPIClient->method('Delete')->will($this->throwException($mockError));

        $accountDocumentAsvClient = new AccountDocumentAsvClient($mockAccountDocumentAsvAPIClient);

        try {
            $accountDocumentAsvClient->DeleteAccountDocument('randomId000000');
            $this->assertFalse(true, "testDeleteAccountDocumentFailureIdDoesn'tExists");
        } catch (IntegrationException $e) {
            $this->assertEquals($e->getCode(), $expectedError->getCode(), "testDeleteAccountDocumentFailureIdDoesn'tExists");
            $this->assertEquals($e->getError(), $expectedError->getError(), "testDeleteAccountDocumentFailureIdDoesn'tExists");
        }
        #T2 ends
    }

    function testFetchMerchantDocumentsAsvClient()
    {
        #T1 starts - Success
        $traceMock = $this->createTraceMock(['count', 'info', 'histogram']);
        $traceMock->expects($this->exactly(2))->method('info');
        $traceMock->expects($this->exactly(2))->method('count');
        $traceMock->expects($this->exactly(1))->method('histogram');

        $merchantId = "10000000000000";
        $entityId = "10000000000001";
        $documentId = "10000000000002";
        $merchantDocumentProto = new \Rzp\Accounts\Account\V1\MerchantDocument(['id' => $documentId, 'merchant_id' => $merchantId, 'entity_id' => $entityId, 'entity_type' => 'merchant']);
        $expectedResponse = new FetchMerchantDocumentsResponse(['documents'=> [$merchantDocumentProto]]);
        $mockAccountDocumentAsvAPIClient = $this->createMock(accountDocumentV1\DocumentAPIClient::class);
        $mockAccountDocumentAsvAPIClient->method('FetchMerchantDocuments')->willReturn($expectedResponse);

        $accountDocumentAsvClient = new AccountDocumentAsvClient($mockAccountDocumentAsvAPIClient);
        $actualResponse = $accountDocumentAsvClient->FetchMerchantDocuments($merchantId);

        $this->assertEquals($actualResponse->serializeToJsonString(), $expectedResponse->serializeToJsonString(), 'testFetchMerchantDocumentsSuccess');
        #T1 ends

        #T2 starts - Failure - Id doesn't exist
        $traceMock = $this->createTraceMock(['count', 'info', 'histogram', 'traceException']);
        $traceMock->expects($this->exactly(1))->method('info');
        $traceMock->expects($this->exactly(1))->method('histogram');
        $traceMock->expects($this->exactly(2))->method('count');
        $traceMock->expects($this->exactly(1))->method('traceException');

        $mockError = new accountDocumentV1\TwirpError('invalid_argument', "id doesn't exists hence can't be deleted");
        $expectedError = new IntegrationException('Could not receive proper response from Account service');
        $mockAccountDocumentAsvAPIClient = $this->createMock(accountDocumentv1\DocumentAPIClient::class);
        $mockAccountDocumentAsvAPIClient->method('FetchMerchantDocuments')->will($this->throwException($mockError));

        $accountDocumentAsvClient = new AccountDocumentAsvClient($mockAccountDocumentAsvAPIClient);

        try {
            $accountDocumentAsvClient->FetchMerchantDocuments('randomId000000');
            $this->assertFalse(true, "testFetchMerchantDocumentsFailureIdDoesn'tExists");
        } catch (IntegrationException $e) {
            $this->assertEquals($e->getCode(), $expectedError->getCode(), "testFetchMerchantDocumentsFailureIdDoesn'tExists");
            $this->assertEquals($e->getError(), $expectedError->getError(), "testFetchMerchantDocumentsFailureIdDoesn'tExists");
        }
        #T2 ends
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
