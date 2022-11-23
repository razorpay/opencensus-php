<?php

namespace Unit\Models\Merchant\Asv\AsvClient;

use Config;
use DG\BypassFinals;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\Acs\AsvClient\AccountAsvClient;
use \RZP\Tests\Functional\TestCase as Testcase;
use Rzp\Accounts\Account\V1 as accountV1;

class AccountAsvClientTest extends TestCase
{
    function setUp(): void
    {
        parent::setUp();
        BypassFinals::enable();
        $asvConfig = [
            'host' => 'https://acs-web.razorpay.com',
            'user' => 'dummy',
            'password' => 'dummy',
            'account_contact_delete_route_http_timeout_sec' => 2,
            'asv_fetch_route_http_timeout_sec' => 2
        ];
        Config::set('applications', ['acs' => $asvConfig]);
    }

    function tearDown(): void
    {
        parent::tearDown();
    }

    function testDeleteAccountContact()
    {
        #T1 starts - Success
        $traceMock = $this->createTraceMock(['count', 'info', 'histogram']);
        $traceMock->expects($this->exactly(2))->method('info');
        $traceMock->expects($this->exactly(2))->method('count');
        $traceMock->expects($this->exactly(1))->method('histogram');

        $expectedResponse = new accountV1\DeleteAccountContactResponse(['deleted' => 'true']);
        $mockAccountAsvAPIClient = $this->createMock(accountV1\AccountAPIClient::class);
        $mockAccountAsvAPIClient->method('DeleteAccountContact')->willReturn($expectedResponse);

        $accountAsvClient = new AccountAsvClient($mockAccountAsvAPIClient);
        $actualResponse = $accountAsvClient->DeleteAccountContact('100000000dummy', '10000000000000', 'support');

        $this->assertEquals($actualResponse->serializeToJsonString(), $expectedResponse->serializeToJsonString(), 'testDeleteAccountContactSuccess');
        #T1 ends

        #T2 starts - Failure - Id doesn't exist
        $traceMock = $this->createTraceMock(['count', 'info', 'histogram', 'traceException']);
        $traceMock->expects($this->exactly(1))->method('info');
        $traceMock->expects($this->exactly(1))->method('histogram');
        $traceMock->expects($this->exactly(2))->method('count');
        $traceMock->expects($this->exactly(1))->method('traceException');

        $mockError = new accountV1\TwirpError('invalid_argument', "account doesn't exists hence contact can't be deleted");
        $expectedError = new IntegrationException('Could not receive proper response from Account service');
        $mockAccountAsvAPIClient = $this->createMock(accountv1\AccountAPIClient::class);
        $mockAccountAsvAPIClient->method('DeleteAccountContact')->will($this->throwException($mockError));

        $accountAsvClient = new AccountAsvClient($mockAccountAsvAPIClient);

        try {
            $accountAsvClient->DeleteAccountContact('10000000000000', 'randomId000000', 'support');
            $this->assertFalse(true, "testDeleteAccountContactFailureAccountIdDoesn'tExists");
        } catch (IntegrationException $e) {
            $this->assertEquals($e->getCode(), $expectedError->getCode(), "testDeleteAccountContactFailureAccountIdDoesn'tExists");
            $this->assertEquals($e->getError(), $expectedError->getError(), "testDeleteAccountContactFailureAccountIdDoesn'tExists");
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
