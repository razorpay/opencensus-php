<?php

namespace RZP\Tests\Unit\PayoutLink;

use Mockery;
use RZP\Constants\Environment;
use RZP\Constants\Mode;
use RZP\Models\BankingAccount\Channel;
use RZP\Models\Merchant;
use RZP\Exception;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\PayoutLink\Service;

class PayoutLinkMicroserviceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function setUpMocksAndFeature(string $methodName) : array
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive($methodName)->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $auth = $this->app['basicauth'];

        $this->app->instance('basicauth', $auth);

        $merchant = $this->fixtures->create('merchant',
            [
                'id'    => '12345678901234'
            ]);

        $auth->setMerchant($merchant);

//        $this->fixtures->create('feature',
//            [
//                'name'      => Constants::X_PAYOUT_LINKS_MS,
//                'entity_id' => '12345678901234'
//            ]);

        // use razorx feature
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === Merchant\RazorxTreatment::RX_PAYOUT_LINK_MICROSERVICE)
                    {
                        return 'on';
                    }

                    return 'off';
                }));

        return [
            'auth' => $auth,
            'mock' => $plMock,
            'service' => new Service()
        ];
    }

    protected function setUpMocksAndFeatureForTestMode() : array
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks')->makePartial()->shouldAllowMockingProtectedMethods();;

        $plMock->shouldReceive("makeRequest");

        $auth = $this->app['basicauth'];

        $this->app->instance('basicauth', $auth);

        $this->app->instance('rzp.mode', "test");

        $merchant = $this->fixtures->create('merchant',
            [
                'id'    => '12345678901234'
            ]);

        $auth->setMerchant($merchant);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === Merchant\RazorxTreatment::RX_PAYOUT_LINK_MICROSERVICE)
                    {
                        return 'on';
                    }

                    return 'off';
                }));

        return [
            'auth' => $auth,
            'service' => new Service(),
            'mock' => $plMock
        ];
    }

    public function testGetSettings()
    {
        $result = $this->setUpMocksAndFeature('getSettings');

        $this->ba->adminAuth();

        $result['service']->getSettings('10000000000000');

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('getSettings');
    }

    public function testUpdateSettings()
    {
        $result = $this->setUpMocksAndFeature('updateSettings');

        $this->ba->adminAuth();

        $result['service']->updateSettings([], '10000000000000');

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('updateSettings');
    }

    public function testCancel()
    {
        $result = $this->setUpMocksAndFeature('cancel');

        $this->ba->privateAuth();

        $result['service']->cancel('');

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('cancel');
    }

    public function testCancelPLForTestMode()
    {
        $result = $this->setUpMocksAndFeatureForTestMode();

        $this->ba->privateAuth();

        try
        {
            $result['service']->cancel('');
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, Exception\BadRequestException::class);
        }
    }

    public function testFetch()
    {
        $result = $this->setUpMocksAndFeature('fetch');

        $this->ba->privateAuth();

        $result['service']->fetchMerchantSpecific('', []);

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('fetch');
    }

    public function testFetchForTestMode()
    {
        $result = $this->setUpMocksAndFeatureForTestMode();

        $this->ba->privateAuth();

        try
        {
            $result['service']->fetchMerchantSpecific('', []);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, Exception\BadRequestException::class);
        }
    }

    public function testFetchMultiple()
    {
        $result = $this->setUpMocksAndFeature('fetchMultiple');

        $this->ba->privateAuth();

        $result['service']->fetchMultipleMerchantSpecific([]);

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('fetchMultiple');
    }

    public function testFetchMultipleForTestMode()
    {
        $result = $this->setUpMocksAndFeatureForTestMode();

        $this->ba->privateAuth();

        try
        {
            $result['service']->fetchMultipleMerchantSpecific([]);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, Exception\BadRequestException::class);
        }
    }

    public function testInitiate()
    {
        $result = $this->setUpMocksAndFeature('initiate');

        $result['service']->initiate('', []);

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('initiate');
    }

    public function testGenerateAndSendCustomerOtp()
    {
        $result = $this->setUpMocksAndFeature('generateAndSendCustomerOtp');

        $result['service']->generateAndSendCustomerOtp('', []);

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('generateAndSendCustomerOtp');
    }

    public function testGetFundAccountsOfContact()
    {
        $result = $this->setUpMocksAndFeature('getFundAccountsOfContact');

        $result['service']->getFundAccountsOfContact('', []);

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('getFundAccountsOfContact');
    }

    public function testGetHostedPageDatat()
    {
        $result = $this->setUpMocksAndFeature('getHostedPageData');

        $result['service']->viewHostedPage('');

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('getHostedPageData');
    }

    public function testVerifyCustomerOtp()
    {
        $result = $this->setUpMocksAndFeature('verifyCustomerOtp');

        $result['service']->verifyCustomerOtp('', []);

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('verifyCustomerOtp');
    }

    public function testResendNotification()
    {
        $result = $this->setUpMocksAndFeature('resendNotification');

        $result['service']->resendNotification('', []);

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('resendNotification');
    }

    public function testResendNotificationForTestMode()
    {
        $result = $this->setUpMocksAndFeatureForTestMode();

        try
        {
            $result['service']->resendNotification('', []);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, Exception\BadRequestException::class);
        }
    }

    public function testOnBoardingStatus()
    {
        $result = $this->setUpMocksAndFeature('onBoardingStatus');

        $result['service']->onBoardingStatus();

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('onBoardingStatus');
    }

    public function testSummary()
    {
        $result = $this->setUpMocksAndFeature('summary');

        $result['service']->summary([]);

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('summary');
    }

    public function testSummaryForTestMode()
    {
        $result = $this->setUpMocksAndFeatureForTestMode();

        try
        {
            $result['service']->summary([]);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, Exception\BadRequestException::class);
        }
    }

    public function testGetBatchSummary()
    {
        $result = $this->setUpMocksAndFeature('getBatchSummary');

        $this->ba->proxyAuth();

        $result['service']->getBatchSummary('');

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('getBatchSummary');
    }

    /**
     * test amazon pay is enabled when
     * 1. settings enabled
     * 2. amount <10000
     * 3. channel != RBL
     */
    public function testGetHostedPageDataForAmazonPay()
    {
        $merchant = new Merchant\Entity();
        $merchant["billing_label"] = "abc";

        $mode["AMAZONPAY"] = 1;
        $mode["UPI"] = 1;
        $response = $this->mockSettingsResponse($mode, 100);

        $bankingAccountMock = $this->mockBankingAccount(Channel::YESBANK);

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->disableOriginalConstructor()
            ->setMethods(array("makeRequest", "getBankingAccountInfo", "getAmazonPayWalletExperienceEnabled", "getEnvironment"))
            ->getMock();
        $mock->method("makeRequest")
            ->willReturn($response);
        $mock->method("getEnvironment")
            ->willReturn(Environment::TESTING);
        $mock->method("getBankingAccountInfo")
            ->willReturn($bankingAccountMock);
        $mock->method("getAmazonPayWalletExperienceEnabled")
            ->willReturn(true);

        $data = $mock->getHostedPageData("poutlk_1000000000", $merchant);
        $this->assertTrue($data['allow_upi']);
        $this->assertTrue($data['allow_amazon_pay']);
    }

    /**
     * test amazon pay is disabled when
     * 1. settings disabled
     * 2. amount <10000
     * 3. channel != RBL
     */
    public function testGetHostedPageDataForAmazonPaySettingsDisabled()
    {
        $merchant = new Merchant\Entity();
        $merchant["billing_label"] = "abc";

        $mode["AMAZONPAY"] = 0;
        $mode["UPI"] = 1;
        $response = $this->mockSettingsResponse($mode,100);

        $bankingAccountMock = $this->mockBankingAccount(Channel::YESBANK);

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->disableOriginalConstructor()
            ->setMethods(array("makeRequest", "getBankingAccountInfo", "getEnvironment", "getAmazonPayWalletExperienceEnabled"))
            ->getMock();
        $mock->method("makeRequest")
            ->willReturn($response);
        $mock->method("getEnvironment")
            ->willReturn(Environment::TESTING);
        $mock->method("getBankingAccountInfo")
            ->willReturn($bankingAccountMock);
        $mock->method("getAmazonPayWalletExperienceEnabled")
            ->willReturn(true);

        $data = $mock->getHostedPageData("poutlk_1000000000", $merchant);
        $this->assertFalse($data['allow_amazon_pay']);
    }

    /**
     * test amazon pay is disabled when
     * 1. settings enabled
     * 2. amount >10000
     * 3. channel != RBL
     */
    public function testGetHostedPageDataForAmazonPayAmountInvalid()
    {
        $merchant = new Merchant\Entity();
        $merchant["billing_label"] = "abc";

        $mode["AMAZONPAY"] = 0;
        $mode["UPI"] = 1;
        $response = $this->mockSettingsResponse($mode, 1000000);

        $bankingAccountMock = $this->mockBankingAccount(Channel::YESBANK);

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->disableOriginalConstructor()
            ->setMethods(array("makeRequest", "getBankingAccountInfo", "getEnvironment", "getAmazonPayWalletExperienceEnabled"))
            ->getMock();
        $mock->method("makeRequest")
            ->willReturn($response);
        $mock->method("getEnvironment")
            ->willReturn(Environment::TESTING);
        $mock->method("getBankingAccountInfo")
            ->willReturn($bankingAccountMock);
        $mock->method("getAmazonPayWalletExperienceEnabled")
            ->willReturn(true);

        $data = $mock->getHostedPageData("poutlk_1000000000", $merchant);
        $this->assertFalse($data['allow_amazon_pay']);
    }

    /**
     * test amazon pay is disabled when
     * 1. settings enabled
     * 2. amount <10000
     * 3. channel == RBL
     */
    public function testGetHostedPageDataForAmazonPayChannelRBL()
    {
        $merchant = new Merchant\Entity();
        $merchant["billing_label"] = "abc";

        // Adding this because checking if a feature is enabled for a merchant requires the merchant ID.
        $merchant["id"] = "12345678901234";

        $mode["AMAZONPAY"] = 0;
        $mode["UPI"] = 1;
        $response = $this->mockSettingsResponse($mode, 10000);

        $bankingAccountMock = $this->mockBankingAccount(Channel::RBL);

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->disableOriginalConstructor()
            ->setMethods(array("makeRequest", "getBankingAccountInfo", "getEnvironment", "getAmazonPayWalletExperienceEnabled"))
            ->getMock();
        $mock->method("makeRequest")
            ->willReturn($response);
        $mock->method("getEnvironment")
            ->willReturn(Environment::TESTING);
        $mock->method("getBankingAccountInfo")
            ->willReturn($bankingAccountMock);
        $mock->method("getAmazonPayWalletExperienceEnabled")
            ->willReturn(true);


        $data = $mock->getHostedPageData("poutlk_1000000000", $merchant);
        $this->assertFalse($data['allow_amazon_pay']);
    }

    public function testUpdateAmazonPaySettings()
    {
        $merchantID = "abcd";
        $trace = \Mockery::mock('RZP\Trace\Trace');
        $trace->shouldReceive("info");
        $this->app->instance("trace", $trace);
        $plMock = $this->getMockBuilder("RZP\Services\PayoutLinks")
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array("makeRequest", "notifySettingsChangeOnSlack"))
            ->getMock();

        $mode["AMAZONPAY"] = "1";
        $response = $this->mockSettingsResponse($mode, 1000);
        $plMock->method("makeRequest")
            ->willReturn($response);
        $plMock->expects($this->exactly(1))
            ->method("notifySettingsChangeOnSlack");
        $input["AMAZONPAY"] = "0";
        $plMock->updateSettings($merchantID, $input);

    }

    public function testUpdateAmazonPaySettingsNegative()
    {
        $merchantID = "abcd";
        $trace = \Mockery::mock('RZP\Trace\Trace');
        $trace->shouldReceive("info");
        $this->app->instance("trace", $trace);
        $plMock = $this->getMockBuilder("RZP\Services\PayoutLinks")
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array("makeRequest", "sendSlackNotification"))
            ->getMock();

        $mode["AMAZONPAY"] = "1";
        $response = $this->mockSettingsResponse($mode, 1000);
        $plMock->method("makeRequest")
            ->willReturn($response);
        $plMock->expects($this->exactly(0))
            ->method("sendSlackNotification");
        $input["AMAZONPAY"] = "1";
        $plMock->updateSettings($merchantID, $input);

    }

    public function testFetchMultiplePL() {
        $input['id'] = 'abc';
        $this->app->instance("rzp.mode", Mode::LIVE);
        $plMock = $this->getMockBuilder("RZP\Services\PayoutLinks")
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array("makeRequest"))
            ->getMock();
        $response['count'] = 1;
        $response['items'] = [];
        $plMock->method('makeRequest')
            ->willReturn($response);
        $output = $plMock->fetchMultiple($input);
        $this->assertEquals($response['count'], $output['count']);
        $this->assertEquals($response['items'], $output['items']);
    }

    public function testFetchMultiplePLWithNoItems() {
        $input['id'] = 'abc';
        $this->app->instance("rzp.mode", Mode::LIVE);
        $plMock = $this->getMockBuilder("RZP\Services\PayoutLinks")
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array("makeRequest"))
            ->getMock();
        $response = array();
        $plMock->method('makeRequest')
            ->willReturn($response);
        $output = $plMock->fetchMultiple($input);
        $this->assertEquals(0, $output['count']);
        $this->assertEquals([], $output['items']);
    }


    private function mockSettingsResponse(array $mode, int $amount)
    {
        $response["settings"] = ["mode" => $mode];
        $response["payout_link_response"]["amount"] = $amount;
        $response["payout_link_response"]["id"] = "poutlk_123456";
        $response["payout_link_response"]["status"] = "issued";
        $response["payout_link_response"]["currency"] = "INR";
        $response["payout_link_response"]["description"] = "testing";
        $response["payout_link_response"]["contact"]["name"] = "ABC";
        $response["payout_link_response"]["contact"]["email"] = "abc@abc.com";
        $response["payout_link_response"]["contact"]["contact"] = "+918877665544";
        return $response;
    }

    private function mockBankingAccount(string $channel)
    {
        $baMock = $this->getMockBuilder('RZP\Models\BankingAccount\Entity')
            ->disableOriginalConstructor()
            ->getMock();
        $baMock->method("getChannel")
            ->willReturn($channel);
        return $baMock;
    }
}

