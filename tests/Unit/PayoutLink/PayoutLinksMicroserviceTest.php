<?php

namespace RZP\Tests\Unit\PayoutLink;

use Mockery;
use RZP\Exception;
use ReflectionClass;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Services\RazorXClient;
use RZP\Constants\Environment;
use RZP\Tests\Functional\TestCase;
use RZP\Models\PayoutLink\Service;
use RZP\Models\BankingAccount\Channel;

class PayoutLinkMicroserviceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function setUpMocksAndFeature(string $methodName, string $mode = Mode::LIVE) : array
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive($methodName)->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $auth = $this->app['basicauth'];

        $this->app->instance('basicauth', $auth);

        $this->app->instance('rzp.mode', $mode);

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
        $result = $this->setUpMocksAndFeature('cancel', 'test');

        $this->ba->privateAuth();

        $result['service']->cancel('');

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('cancel');
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
        $result = $result = $this->setUpMocksAndFeature('fetch', 'test');

        $this->ba->privateAuth();

        $result['service']->fetchMerchantSpecific('', []);

        $result['mock']->shouldHaveReceived('fetch');
    }

    public function testFetchMultiple()
    {
        $result = $this->setUpMocksAndFeature('fetchMultiple');

        $this->ba->privateAuth();

        $result['service']->fetchMultipleMerchantSpecific([], "");

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('fetchMultiple');
    }

    public function testFetchMultipleForTestMode()
    {
        $result = $this->setUpMocksAndFeature('fetchMultiple', 'test');

        $this->ba->privateAuth();

        $result['service']->fetchMultipleMerchantSpecific([], "");

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('fetchMultiple');
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
        $result = $this->setUpMocksAndFeature('summary', 'test');

        $result['service']->summary([]);

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('summary');
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
            ->setMethods(array("makeRequest", "getBankingAccountInfo", "getAmazonPayWalletFeatureEnabled", "getEnvironment", "getKeylessHeader", "getModeForPublicPage"))
            ->getMock();
        $mock->method("makeRequest")
            ->willReturn($response);
        $mock->method("getEnvironment")
            ->willReturn(Environment::TESTING);
        $mock->method("getBankingAccountInfo")
            ->willReturn($bankingAccountMock);
        $mock->method("getAmazonPayWalletFeatureEnabled")
            ->willReturn(true);
        $mock->method("getKeylessHeader")
            ->willReturn(null);
        $mock->method("getModeForPublicPage")
            ->willReturn('live');

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
            ->setMethods(array("makeRequest", "getBankingAccountInfo", "getEnvironment", "getAmazonPayWalletFeatureEnabled", "getKeylessHeader", "getModeForPublicPage"))
            ->getMock();
        $mock->method("makeRequest")
            ->willReturn($response);
        $mock->method("getEnvironment")
            ->willReturn(Environment::TESTING);
        $mock->method("getBankingAccountInfo")
            ->willReturn($bankingAccountMock);
        $mock->method("getAmazonPayWalletFeatureEnabled")
            ->willReturn(true);
        $mock->method("getKeylessHeader")
            ->willReturn(null);
        $mock->method("getModeForPublicPage")
            ->willReturn('live');

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
            ->setMethods(array("makeRequest", "getBankingAccountInfo", "getEnvironment", "getAmazonPayWalletFeatureEnabled", "getKeylessHeader", "getModeForPublicPage"))
            ->getMock();
        $mock->method("makeRequest")
            ->willReturn($response);
        $mock->method("getEnvironment")
            ->willReturn(Environment::TESTING);
        $mock->method("getBankingAccountInfo")
            ->willReturn($bankingAccountMock);
        $mock->method("getAmazonPayWalletFeatureEnabled")
            ->willReturn(true);
        $mock->method("getKeylessHeader")
            ->willReturn(null);
        $mock->method("getModeForPublicPage")
            ->willReturn('live');

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
            ->setMethods(array("makeRequest", "getBankingAccountInfo", "getEnvironment", "getAmazonPayWalletFeatureEnabled", "getKeylessHeader", "getModeForPublicPage"))
            ->getMock();
        $mock->method("makeRequest")
            ->willReturn($response);
        $mock->method("getEnvironment")
            ->willReturn(Environment::TESTING);
        $mock->method("getBankingAccountInfo")
            ->willReturn($bankingAccountMock);
        $mock->method("getAmazonPayWalletFeatureEnabled")
            ->willReturn(true);
        $mock->method("getKeylessHeader")
            ->willReturn(null);
        $mock->method("getModeForPublicPage")
            ->willReturn('live');


        $data = $mock->getHostedPageData("poutlk_1000000000", $merchant);
        $this->assertFalse($data['allow_amazon_pay']);
    }

    public function testUpdateAmazonPaySettings()
    {
        $merchantID = "abcd";
        $trace = \Mockery::mock('RZP\Trace\Trace');
        $trace->shouldReceive("info");
        $this->app->instance("trace", $trace);
        $this->app->instance("rzp.mode", 'live');
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
        $this->app->instance("rzp.mode", "live");
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

    public function testMaskedGetHostedPageDataForVPA()
    {
        $newMerchant = $this->fixtures->create('merchant');

        $this->prepareBankingAccountData($newMerchant->getId());

        $vpaDetails = $this->fixtures->create('vpa', [
            'username' => 'testing',
            'handle'   => 'handle',
        ]);
        $this->fixtures->create('fund_account', [
            'id'          => '100000000009fa',
            'source_type' => 'contact',
            'source_id'   => '1001contact',
            'merchant_id' => $newMerchant->getId(),
            'account_type'=> 'vpa',
            'account_id'  => $vpaDetails->getId(),
        ]);

        $response = $this->mockGetHostedResponseForProcessedPL('100000000009fa');

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest', 'allowUpi', 'allowAmazonPay', 'getEnvironment'))
            ->getMock();
        $mock->method('makeRequest')->willReturn($response);
        $mock->method('allowUpi')->willReturn(false);
        $mock->method('allowAmazonPay')->willReturn(false);
        $mock->method('getEnvironment')->willReturn(Environment::TESTING);


        $data = $mock->getHostedPageData('poutlk_1000000000', $newMerchant);
        $faDetailsInResponse = json_decode($data['fund_account_details']);
        $this->assertEquals('123*********', $data['payout_utr'], "Payout UTR not masked");
        $this->assertEquals('te*****', $faDetailsInResponse->vpa->username, "VPA Username not masked");
        $this->assertEquals('h*****', $faDetailsInResponse->vpa->handle, "VPA Handle not masked");
        $this->assertEquals('te*****@h*****', $faDetailsInResponse->vpa->address, "VPA Address not masked");
    }

    public function testMaskedGetHostedPageDataForBankAccount()
    {
        $newMerchant = $this->fixtures->create('merchant');

        $this->prepareBankingAccountData($newMerchant->getId());

        $baDetails = $this->fixtures->create('bank_account', [
            'ifsc_code' => 'IFSC12345',
            'account_number'   => '89898989898989',
            'beneficiary_name' => 'testing',
        ]);
        $this->fixtures->create('fund_account', [
            'id'          => '100000000010fa',
            'source_type' => 'contact',
            'source_id'   => '1001contact',
            'merchant_id' => $newMerchant->getId(),
            'account_type'=> 'bank_account',
            'account_id'  => $baDetails->getId(),
        ]);

        $response = $this->mockGetHostedResponseForProcessedPL('100000000010fa');

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest', 'allowUpi', 'allowAmazonPay', 'getEnvironment'))
            ->getMock();
        $mock->method('makeRequest')->willReturn($response);
        $mock->method('allowUpi')->willReturn(false);
        $mock->method('allowAmazonPay')->willReturn(false);
        $mock->method('getEnvironment')->willReturn(Environment::TESTING);


        $data = $mock->getHostedPageData('poutlk_1000000000', $newMerchant);
        $faDetailsInResponse = json_decode($data['fund_account_details']);
        $this->assertEquals('123*********', $data['payout_utr'], "Payout UTR not masked");
        $this->assertEquals('te*****', $faDetailsInResponse->bank_account->name, "Name not masked");
        $this->assertEquals('IF*******', $faDetailsInResponse->bank_account->ifsc, "IFSC Code not masked");
        $this->assertEquals('8989**********', $faDetailsInResponse->bank_account->account_number, "Account Number not masked");
    }

    public function testMaskedGetHostedPageDataForWalletAccount()
    {
        $newMerchant = $this->fixtures->create('merchant');

        $this->prepareBankingAccountData($newMerchant->getId());

        $walletDetails = $this->fixtures->create('wallet_account', [
            'phone' => '9040434917',
            'email' => 'testing@gmail.com',
            'name'  => 'testing',
        ]);
        $this->fixtures->create('fund_account', [
            'id'          => '100000000011fa',
            'source_type' => 'contact',
            'source_id'   => '1001contact',
            'merchant_id' => $newMerchant->getId(),
            'account_type'=> 'wallet_account',
            'account_id'  => $walletDetails->getId(),
        ]);

        $response = $this->mockGetHostedResponseForProcessedPL('100000000011fa');

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest', 'allowUpi', 'allowAmazonPay', 'getEnvironment'))
            ->getMock();
        $mock->method('makeRequest')->willReturn($response);
        $mock->method('allowUpi')->willReturn(false);
        $mock->method('allowAmazonPay')->willReturn(false);
        $mock->method('getEnvironment')->willReturn(Environment::TESTING);


        $data = $mock->getHostedPageData('poutlk_1000000000', $newMerchant);
        $faDetailsInResponse = json_decode($data['fund_account_details']);
        $this->assertEquals('123*********', $data['payout_utr'], "Payout UTR not masked");
        $this->assertEquals('te*****', $faDetailsInResponse->wallet->name, "Name not masked");
        $this->assertEquals('90******17', $faDetailsInResponse->wallet->phone, "Phone not masked");
        $this->assertEquals('te*****@g***l.com', $faDetailsInResponse->wallet->email, "Email not masked");
    }

    public function testMaskedGetHostedPageDataForContactNameAndDescription()
    {
        $newMerchant = $this->fixtures->create('merchant');

        $this->prepareBankingAccountData($newMerchant->getId());

        $walletDetails = $this->fixtures->create('wallet_account', [
            'phone' => '9040434917',
            'email' => 'testing@gmail.com',
            'name'  => 'testing',
        ]);
        $this->fixtures->create('fund_account', [
            'id'          => '100000000011fa',
            'source_type' => 'contact',
            'source_id'   => '1001contact',
            'merchant_id' => $newMerchant->getId(),
            'account_type'=> 'wallet_account',
            'account_id'  => $walletDetails->getId(),
        ]);

        $response = $this->mockGetHostedResponseForProcessedPL('100000000011fa');

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest', 'allowUpi', 'allowAmazonPay', 'getEnvironment'))
            ->getMock();
        $mock->method('makeRequest')->willReturn($response);
        $mock->method('allowUpi')->willReturn(false);
        $mock->method('allowAmazonPay')->willReturn(false);
        $mock->method('getEnvironment')->willReturn(Environment::TESTING);


        $data = $mock->getHostedPageData('poutlk_1000000000', $newMerchant);
        $this->assertEquals('t***', $data['user_name'], "contact name not masked");
        //for processed payout link, the description will be masked
        $this->assertEquals('te*****', $data['description'], "description not masked");
    }

    public function testNonMaskedDescriptionForGetHostedPageForIssuedPayoutLink()
    {
        $newMerchant = $this->fixtures->create('merchant');

        $this->prepareBankingAccountData($newMerchant->getId());

        $response = $this->mockGetHostedResponseForIssuedPL();

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest', 'allowUpi', 'allowAmazonPay', 'getEnvironment'))
            ->getMock();
        $mock->method('makeRequest')->willReturn($response);
        $mock->method('allowUpi')->willReturn(false);
        $mock->method('allowAmazonPay')->willReturn(false);
        $mock->method('getEnvironment')->willReturn(Environment::TESTING);

        $data = $mock->getHostedPageData('poutlk_1000000000', $newMerchant);
        //for issued payout link, the description will not be masked
        $this->assertEquals('testing', $data['description'], "description incorrect");
    }

    public function testMaskedDescriptionForGetHostedPageForCancelledPayoutLink()
    {
        $newMerchant = $this->fixtures->create('merchant');

        $this->prepareBankingAccountData($newMerchant->getId());

        $response = $this->mockGetHostedResponseForCancelledPL();

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest', 'allowUpi', 'allowAmazonPay', 'getEnvironment'))
            ->getMock();
        $mock->method('makeRequest')->willReturn($response);
        $mock->method('allowUpi')->willReturn(false);
        $mock->method('allowAmazonPay')->willReturn(false);
        $mock->method('getEnvironment')->willReturn(Environment::TESTING);

        $data = $mock->getHostedPageData('poutlk_1000000000', $newMerchant);
        //for cancelled payout link, the description will be masked
        $this->assertEquals('te*****', $data['description'], "description not masked");
    }

    public function testDescriptionMaskingForGetHostedPageForIssuedPayoutLinkEmptyDescription()
    {
        $newMerchant = $this->fixtures->create('merchant');

        $this->prepareBankingAccountData($newMerchant->getId());

        $response = $this->mockGetHostedResponseForIssuedPLEmptyDescription();

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest', 'allowUpi', 'allowAmazonPay', 'getEnvironment'))
            ->getMock();
        $mock->method('makeRequest')->willReturn($response);
        $mock->method('allowUpi')->willReturn(false);
        $mock->method('allowAmazonPay')->willReturn(false);
        $mock->method('getEnvironment')->willReturn(Environment::TESTING);

        $data = $mock->getHostedPageData('poutlk_1000000000', $newMerchant);
        //checking that the utility method mask_by_percentage works fine for empty string
        $this->assertEquals('', $data['description'], "description not empty");
    }

    public function testDescriptionMaskingForGetHostedPageForIssuedPayoutLinkNullDescription()
    {
        $newMerchant = $this->fixtures->create('merchant');

        $this->prepareBankingAccountData($newMerchant->getId());

        $response = $this->mockGetHostedResponseForIssuedPLNullDescription();

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest', 'allowUpi', 'allowAmazonPay', 'getEnvironment'))
            ->getMock();
        $mock->method('makeRequest')->willReturn($response);
        $mock->method('allowUpi')->willReturn(false);
        $mock->method('allowAmazonPay')->willReturn(false);
        $mock->method('getEnvironment')->willReturn(Environment::TESTING);

        $data = $mock->getHostedPageData('poutlk_1000000000', $newMerchant);
        //checking that the utility method mask_by_percentage works fine for null string
        $this->assertEquals(null, $data['description'], "description not null");
    }

    private function prepareBankingAccountData($merchantId)
    {
        $xBalance1 = $this->fixtures->create('balance',
            [
                'merchant_id'       => $merchantId,
                'type'              => 'banking',
                'account_type'      => 'shared',
                'account_number'    => '2224440041626905',
                'balance'           => 300,
            ]);

        $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => $merchantId,
            'balance_id'            => $xBalance1->getId(),
            'channel'               => 'yesbank',
            'status'                => 'activated',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);
    }

    private function mockGetHostedResponseForProcessedPL(string $faId)
    {
        $mode['AMAZONPAY'] = 1;
        $mode['UPI'] = 1;
        $payoutCollection = [
            'entity' => 'collection',
            'count' => 1,
            'items' => [
                [
                    'id' => 'pout_some-id',
                    'utr' => '123456789987',
                    'mode' => 'UPI',
                ]
            ],
        ];
        $response['settings'] = ['mode' => $mode];
        $response['payout_link_response']['amount'] = 1000;
        $response['payout_link_response']['id'] = 'poutlk_123456';
        $response['payout_link_response']['status'] = 'processed';
        $response["payout_link_response"]["account_number"] = "2224440041626905";
        $response['payout_link_response']['fund_account_id'] = 'fa_'.$faId;
        $response['payout_link_response']['currency'] = 'INR';
        $response['payout_link_response']['description'] = 'testing';
        $response['payout_link_response']['contact']['name'] = 'test';
        $response['payout_link_response']['contact']['email'] = 'test@gmail.com';
        $response['payout_link_response']['contact']['contact'] = '+919090990909';
        $response['payout_link_response']['payouts'] = $payoutCollection;
        return $response;
    }

    private function mockGetHostedResponseForIssuedPL()
    {
        $mode['AMAZONPAY'] = 1;
        $mode['UPI'] = 1;
        $response['settings'] = ['mode' => $mode];
        $response['payout_link_response']['amount'] = 1000;
        $response['payout_link_response']['id'] = 'poutlk_123456';
        $response["payout_link_response"]["account_number"] = "2224440041626905";
        $response['payout_link_response']['status'] = 'issued';
        $response['payout_link_response']['currency'] = 'INR';
        $response['payout_link_response']['description'] = 'testing';
        $response['payout_link_response']['contact']['name'] = 'test';
        $response['payout_link_response']['contact']['email'] = 'test@gmail.com';
        $response['payout_link_response']['contact']['contact'] = '+919090990909';
        return $response;
    }

    private function mockGetHostedResponseForCancelledPL()
    {
        $mode['AMAZONPAY'] = 1;
        $mode['UPI'] = 1;
        $response['settings'] = ['mode' => $mode];
        $response['payout_link_response']['amount'] = 1000;
        $response['payout_link_response']['id'] = 'poutlk_123456';
        $response["payout_link_response"]["account_number"] = "2224440041626905";
        $response['payout_link_response']['status'] = 'cancelled';
        $response['payout_link_response']['currency'] = 'INR';
        $response['payout_link_response']['description'] = 'testing';
        $response['payout_link_response']['contact']['name'] = 'test';
        $response['payout_link_response']['contact']['email'] = 'test@gmail.com';
        $response['payout_link_response']['contact']['contact'] = '+919090990909';
        return $response;
    }

    private function mockGetHostedResponseForIssuedPLEmptyDescription()
    {
        $mode['AMAZONPAY'] = 1;
        $mode['UPI'] = 1;
        $response['settings'] = ['mode' => $mode];
        $response['payout_link_response']['amount'] = 1000;
        $response['payout_link_response']['id'] = 'poutlk_123456';
        $response["payout_link_response"]["account_number"] = "2224440041626905";
        $response['payout_link_response']['status'] = 'issued';
        $response['payout_link_response']['currency'] = 'INR';
        $response['payout_link_response']['description'] = '';
        $response['payout_link_response']['contact']['name'] = 'test';
        $response['payout_link_response']['contact']['email'] = 'test@gmail.com';
        $response['payout_link_response']['contact']['contact'] = '+919090990909';
        return $response;
    }

    private function mockGetHostedResponseForIssuedPLNullDescription()
    {
        $mode['AMAZONPAY'] = 1;
        $mode['UPI'] = 1;
        $response['settings'] = ['mode' => $mode];
        $response['payout_link_response']['amount'] = 1000;
        $response['payout_link_response']['id'] = 'poutlk_123456';
        $response["payout_link_response"]["account_number"] = "2224440041626905";
        $response['payout_link_response']['status'] = 'issued';
        $response['payout_link_response']['currency'] = 'INR';
        $response['payout_link_response']['contact']['name'] = 'test';
        $response['payout_link_response']['contact']['email'] = 'test@gmail.com';
        $response['payout_link_response']['contact']['contact'] = '+919090990909';
        return $response;
    }


    private function mockSettingsResponse(array $mode, int $amount)
    {
        $response["settings"] = ["mode" => $mode];
        $response["payout_link_response"]["amount"] = $amount;
        $response["payout_link_response"]["id"] = "poutlk_123456";
        $response["payout_link_response"]["account_number"] = "2224440041626905";
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

    /**
     * test: upi is always enabled and amazon pay is always disabled for ICICI
     * 1. settings enabled
     * 2. amount <10000
     * 3. channel = ICICI
     */
    public function testHostedPageForIciciBankingAccount()
    {
        $merchantId = '10000000000000';

        $iciciAccountNumber = '9177278012';

        $payoutLinkId = 'poutlk_1000000000';

        $this->setupMerchantBankingAccounts($merchantId, $iciciAccountNumber);

        $response = $this->mockedMSResponse($payoutLinkId, $iciciAccountNumber);

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->disableOriginalConstructor()
            ->setMethods(array('makeRequest', 'getEnvironment', 'getKeylessHeader', "getModeForPublicPage"))
            ->getMock();

        $mock->method('makeRequest')
            ->willReturn($response);

        $mock->method('getEnvironment')
            ->willReturn(Environment::TESTING);

        $mock->method('getKeylessHeader')
            ->willReturn(null);

        $mock->method('getModeForPublicPage')
            ->willReturn('live');

        $data = $mock->getHostedPageData($payoutLinkId, $this->getMerchantEntity($merchantId));

        $this->assertEquals(true, $data['allow_upi']);

        $this->assertEquals(false, $data['allow_amazon_pay']);
    }

    private function setupMerchantBankingAccounts($merchantId, $iciciAccountNumber)
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);

        // create a icici balance. mock will take care of creating a banking account for icici
        $this->fixtures->create('balance', [
            'merchant_id'    => $merchantId,
            'account_type'   => 'direct',
            'type'           => 'banking',
            'channel'        => 'icici',
            'balance'        => 10000000,
            'account_number' => $iciciAccountNumber,
        ]);

        $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => $merchantId,
            'channel'               => 'yesbank',
            'status'                => 'activated',
        ]);
    }

    private function getMerchantEntity($merchantId)
    {
        $merchant = new Merchant\Entity();

        $merchant['billing_label'] = 'abc';

        $merchant['id'] = $merchantId;

        return $merchant;
    }

    private function mockedMSResponse($payoutLinkId, $accountNumber)
    {
        $mode['AMAZONPAY'] = 1;
        $mode['UPI'] = 1;
        $response['settings'] = ['mode' => $mode];
        $response['payout_link_response']['amount'] = 1000;
        $response['payout_link_response']['id'] = $payoutLinkId;
        $response['payout_link_response']['status'] = 'issued';
        $response['payout_link_response']['account_number'] = $accountNumber;
        $response['payout_link_response']['currency'] = 'INR';
        $response['payout_link_response']['description'] = 'testing';
        $response['payout_link_response']['contact']['name'] = 'ABC';
        $response['payout_link_response']['contact']['email'] = 'abc@abc.com';
        $response['payout_link_response']['contact']['contact'] = '+918877665544';
        return $response;
    }

    public function testProcessParametersForPayoutsExpandPayoutsMissingInResponse()
    {
        $mockedMSResponse = $this->getDefaultPayoutLinkEntityArray();

        // emptying the payouts in response
        $mockedMSResponse['payouts'] = [];

        $this->app->instance('rzp.mode', 'live');

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest'))
            ->getMock();
        $mock->method('makeRequest')->willReturn($mockedMSResponse);

        $input = [
            'expand' => [
                '0' => 'payouts'
            ]
        ];

        $response = $mock->fetch('poutlk_link-id', $input, '10000000000000');

        // response should now contain payouts collection 3 items...entity, count, items
        $this->assertArrayHasKey('payouts', $response);
        $this->assertEquals(3, sizeof($response['payouts']));

        // entity -> 'collection', 'count' -> 0,  sizeof('items') -> 0
        $this->assertEquals('collection', $response['payouts']['entity']);
        $this->assertEquals(0, $response['payouts']['count']);
        $this->assertEquals(0, sizeof($response['payouts']['items']));
    }

    public function testProcessParametersForPayoutsExpandPayoutsPresentInResponse()
    {
        $mockedMSResponse = $this->getDefaultPayoutLinkEntityArray();

        $this->app->instance('rzp.mode', 'live');

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest'))
            ->getMock();
        $mock->method('makeRequest')->willReturn($mockedMSResponse);

        $input = [
            'expand' => [
                '0' => 'payouts'
            ]
        ];

        $response = $mock->fetch('poutlk_link-id', $input, '10000000000000');

        // response should now contain payouts collection 3 items...entity, count, items
        $this->assertArrayHasKey('payouts', $response);
        $this->assertEquals(3, sizeof($response['payouts']));

        // entity -> 'collection', non-empty 'count', non-empty  'items'
        $this->assertEquals('collection', $response['payouts']['entity']);
        $this->assertNotEmpty($response['payouts']['count']);
        $this->assertNotEmpty(sizeof($response['payouts']['items']));

        // only payouts in expand. so fund_account and user should not be present in response
        $this->assertArrayNotHasKey('fund_account', $response);
        $this->assertArrayNotHasKey('user', $response);
    }

    public function testProcessParametersForFundAccountExpandFundAccountMissingInResponse()
    {
        $mockedMSResponse = $this->getDefaultPayoutLinkEntityArray();

        // emptying the fund-account in response
        $mockedMSResponse['fund_account'] = [];

        $this->app->instance('rzp.mode', 'live');

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest'))
            ->getMock();
        $mock->method('makeRequest')->willReturn($mockedMSResponse);

        $input = [
            'expand' => [
                '0' => 'fund_account'
            ]
        ];

        $response = $mock->fetch('poutlk_link-id', $input, '10000000000000');

        // response should now contain fund-account with value as null
        $this->assertArrayHasKey('fund_account', $response);
        $this->assertEquals(null, $response['fund_account']);

        // only fund_account in expand. so payouts and user should not be present in response
        $this->assertArrayNotHasKey('user', $response);
        $this->assertArrayNotHasKey('payouts', $response);
    }

    public function testProcessParametersForFundAccountExpandFundAccountPresentInResponse()
    {
        $mockedMSResponse = $this->getDefaultPayoutLinkEntityArray();

        $this->app->instance('rzp.mode', 'live');

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest'))
            ->getMock();
        $mock->method('makeRequest')->willReturn($mockedMSResponse);

        $input = [
            'expand' => [
                '0' => 'fund_account'
            ]
        ];

        $response = $mock->fetch('poutlk_link-id', $input, '10000000000000');

        // response should now contain fund-account with value as null
        $this->assertArrayHasKey('fund_account', $response);
        $this->assertNotEmpty($response['fund_account']);

        // only fund_account in expand. so payouts and user should not be present in response
        $this->assertArrayNotHasKey('user', $response);
        $this->assertArrayNotHasKey('payouts', $response);
    }

    public function testProcessParametersForUserExpandUserMissingInResponse()
    {
        $mockedMSResponse = $this->getDefaultPayoutLinkEntityArray();

        // emptying the user in response
        $mockedMSResponse['user'] = [];

        $this->app->instance('rzp.mode', 'live');

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest'))
            ->getMock();
        $mock->method('makeRequest')->willReturn($mockedMSResponse);

        $input = [
            'expand' => [
                '0' => 'user'
            ]
        ];

        $response = $mock->fetch('poutlk_link-id', $input, '10000000000000');

        // response should now contain user with nuull value
        $this->assertArrayHasKey('user', $response);
        $this->assertEquals(null, $response['user']);

        // only user in expand. so payouts and fund_account should not be present in response
        $this->assertArrayNotHasKey('payouts', $response);
        $this->assertArrayNotHasKey('fund_account', $response);
    }

    public function testProcessParametersForUserExpandUserPresentInResponse()
    {
        $mockedMSResponse = $this->getDefaultPayoutLinkEntityArray();

        $this->app->instance('rzp.mode', 'live');

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest'))
            ->getMock();
        $mock->method('makeRequest')->willReturn($mockedMSResponse);

        $input = [
            'expand' => [
                '0' => 'user'
            ]
        ];

        $response = $mock->fetch('poutlk_link-id', $input, '10000000000000');

        // response should now contain fund-account with value as null
        $this->assertArrayHasKey('user', $response);
        $this->assertNotEmpty($response['user']);

        // only user in expand. so payouts and fund_account should not be present in response
        $this->assertArrayNotHasKey('payouts', $response);
        $this->assertArrayNotHasKey('fund_account', $response);
    }

    protected function getDefaultPayoutLinkEntityArray()
    {
        return [
            'id' => 'poutlk_link-id',
            'entity' => 'payout_link',
            'contact' => [
                'name' => 'Test Contact',
                'contact' => '0000000000',
                'email' => 'test-contact@gmail.com'
            ],
            'status' => 'processed',
            'amount' => 1000,
            'attempt_count' => 1,
            'fund_account_id' => 'fa_fa-id-2',
            'currency' => 'INR',
            'description' => 'Test  Description',
            'account_number' => '878780080316316',
            'merchant_id' => '10000000000000',
            'short_url' => 'https://sample/short/url',
            'contact_id' => 'cont_contact-id',
            'send_sms' => 'true',
            'send_email' => 'true',
            'receipt' => 'Receipt',
            'user_id' => 'user-id',
            'payouts' => [
                'count' => 2,
                'entity' => 'collection',
                'items' => [
                    [
                        'id' => 'pout_payout-id-1',
                        'amount' => 1000,
                        'fund_account_id' => 'fa_fa-id-1',
                        'status' => 'failed',
                        'purpose' => 'purpose',
                        'mode' => 'IMPS',
                        'entity' => 'payout',
                        'failure_reason' => 'some failure reason'
                    ],
                    [
                        'id' => 'pout_payout-id-2',
                        'amount' => 1000,
                        'fund_account_id' => 'fa_fa-id-2',
                        'status' => 'processed',
                        'purpose' => 'purpose',
                        'mode' => 'IMPS',
                        'entity' => 'payout',
                    ]
                ]
            ],
            'fund_account' => [
                'account_type' => 'vpa',
                'active' => true,
                'contact_id' => 'cont_contact-id',
                'entity' => 'fund-account',
                'id' => 'fa_fa-id-2',
                'vpa' => [
                    'address' => 'test@upi'
                ]
            ],
            'user' => [
                'account_locked' => false,
                'confirmed' => true,
                'contact_mobile' => '9999999999',
                'contact_mobile_verified' => true,
                'email' => 'test@gmail.com',
                'id' => 'user-id',
                'name' => 'Test Contact',
                'restricted' => false
            ]
        ];
    }

    private function mockGetHostedResponseForExpiredPL()
    {
        $mode['AMAZONPAY'] = 1;
        $mode['UPI'] = 1;
        $mode['support_contact'] = '9040434917';
        $mode['support_email'] = 'test@razorpay';
        $response['settings'] = ['mode' => $mode];
        $response['payout_link_response']['amount'] = 1000;
        $response['payout_link_response']['id'] = 'poutlk_123456';
        $response['payout_link_response']['status'] = 'expired';
        $response["payout_link_response"]["account_number"] = "2224440041626905";
        $response['payout_link_response']['currency'] = 'INR';
        $response['payout_link_response']['description'] = 'testing';
        $response['payout_link_response']['contact']['name'] = 'test';
        $response['payout_link_response']['contact']['email'] = 'test@gmail.com';
        $response['payout_link_response']['contact']['contact'] = '+919090990909';
        $response['payout_link_response']['expired_at'] = 1571656972;
        return $response;
    }

    private function mockGetHostedResponseForIssuedPLWithSupportDetails()
    {
        $mode['AMAZONPAY'] = 1;
        $mode['UPI'] = 1;
        $mode['support_contact'] = '9040434917';
        $mode['support_email'] = 'test@razorpay';
        $response['settings'] = ['mode' => $mode];
        $response['payout_link_response']['amount'] = 1000;
        $response['payout_link_response']['id'] = 'poutlk_123456';
        $response['payout_link_response']['status'] = 'processed';
        $response["payout_link_response"]["account_number"] = "2224440041626905";
        $response['payout_link_response']['currency'] = 'INR';
        $response['payout_link_response']['description'] = 'testing';
        $response['payout_link_response']['contact']['name'] = 'test';
        $response['payout_link_response']['contact']['email'] = 'test@gmail.com';
        $response['payout_link_response']['contact']['contact'] = '+919090990909';
        $response['payout_link_response']['expired_at'] = 1571656972;
        return $response;
    }

    public function testNonEmptySupportDetailsForGetHostedPageForExpiredPayoutLink()
    {
        $newMerchant = $this->fixtures->create('merchant');

        $this->prepareBankingAccountData($newMerchant->getId());

        $response = $this->mockGetHostedResponseForExpiredPL();

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest', 'allowUpi', 'allowAmazonPay', 'getEnvironment'))
            ->getMock();
        $mock->method('makeRequest')->willReturn($response);
        $mock->method('allowUpi')->willReturn(false);
        $mock->method('allowAmazonPay')->willReturn(false);
        $mock->method('getEnvironment')->willReturn(Environment::TESTING);

        $data = $mock->getHostedPageData('poutlk_1000000000', $newMerchant);

        $this->assertEquals(1571656972, $data['expired_at'], 'expired_at missing');

        $this->assertEquals('9040434917', $data['support_phone'], 'incorrect support phone');

        $this->assertEquals('test@razorpay', $data['support_email'], 'incorrect support email');
    }

    public function testEmptySupportDetailsForGetHostedPageForIssuedPayoutLink()
    {
        $newMerchant = $this->fixtures->create('merchant');

        $this->prepareBankingAccountData($newMerchant->getId());

        $response = $this->mockGetHostedResponseForIssuedPLWithSupportDetails();

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest', 'allowUpi', 'allowAmazonPay', 'getEnvironment'))
            ->getMock();
        $mock->method('makeRequest')->willReturn($response);
        $mock->method('allowUpi')->willReturn(false);
        $mock->method('allowAmazonPay')->willReturn(false);
        $mock->method('getEnvironment')->willReturn(Environment::TESTING);

        $data = $mock->getHostedPageData('poutlk_1000000000', $newMerchant);

        $this->assertEmpty($data['support_phone'], 'support phone present in data');

        $this->assertEmpty($data['support_email'], 'support email present in data');
    }

    public function testExpireByForGetHostedPageForPayoutLinkWithExpiry()
    {
        $newMerchant = $this->fixtures->create('merchant');

        $this->prepareBankingAccountData($newMerchant->getId());

        $response = $this->mockGetHostedResponseForPLWithExpiry();

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest', 'allowUpi', 'allowAmazonPay', 'getEnvironment'))
            ->getMock();
        $mock->method('makeRequest')->willReturn($response);
        $mock->method('allowUpi')->willReturn(false);
        $mock->method('allowAmazonPay')->willReturn(false);
        $mock->method('getEnvironment')->willReturn(Environment::TESTING);

        $data = $mock->getHostedPageData('poutlk_1000000000', $newMerchant);

        $this->assertNotEmpty($data['expire_by'], 'expire_by not present in data');
    }

    public function testExpireByForGetHostedPageForPayoutLinkWithoutExpiry()
    {
        $newMerchant = $this->fixtures->create('merchant');

        $this->prepareBankingAccountData($newMerchant->getId());

        $response = $this->mockGetHostedResponseForPLWithoutExpiry();

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest', 'allowUpi', 'allowAmazonPay', 'getEnvironment'))
            ->getMock();
        $mock->method('makeRequest')->willReturn($response);
        $mock->method('allowUpi')->willReturn(false);
        $mock->method('allowAmazonPay')->willReturn(false);
        $mock->method('getEnvironment')->willReturn(Environment::TESTING);

        $data = $mock->getHostedPageData('poutlk_1000000000', $newMerchant);

        $this->assertEquals(0, $data['expire_by'], 'expire_by not present in data');
    }

    private function mockGetHostedResponseForPLWithExpiry()
    {
        $mode['AMAZONPAY'] = 1;
        $mode['UPI'] = 1;
        $mode['support_contact'] = '9040434917';
        $mode['support_email'] = 'test@razorpay';
        $response['settings'] = ['mode' => $mode];
        $response['payout_link_response']['amount'] = 1000;
        $response['payout_link_response']['id'] = 'poutlk_123456';
        $response['payout_link_response']['status'] = 'processed';
        $response["payout_link_response"]["account_number"] = "2224440041626905";
        $response['payout_link_response']['currency'] = 'INR';
        $response['payout_link_response']['description'] = 'testing';
        $response['payout_link_response']['contact']['name'] = 'test';
        $response['payout_link_response']['contact']['email'] = 'test@gmail.com';
        $response['payout_link_response']['contact']['contact'] = '+919090990909';
        $response['payout_link_response']['expire_by'] = 1571656972;
        return $response;
    }

    private function mockGetHostedResponseForPLWithoutExpiry()
    {
        $mode['AMAZONPAY'] = 1;
        $mode['UPI'] = 1;
        $mode['support_contact'] = '9040434917';
        $mode['support_email'] = 'test@razorpay';
        $response['settings'] = ['mode' => $mode];
        $response['payout_link_response']['amount'] = 1000;
        $response['payout_link_response']['id'] = 'poutlk_123456';
        $response['payout_link_response']['status'] = 'processed';
        $response["payout_link_response"]["account_number"] = "2224440041626905";
        $response['payout_link_response']['currency'] = 'INR';
        $response['payout_link_response']['description'] = 'testing';
        $response['payout_link_response']['contact']['name'] = 'test';
        $response['payout_link_response']['contact']['email'] = 'test@gmail.com';
        $response['payout_link_response']['contact']['contact'] = '+919090990909';
        return $response;
    }

    public function testGetHostedPageForKeylessHeaderEnabledMerchant()
    {
        $newMerchant = $this->fixtures->create('merchant');

        $this->prepareBankingAccountData($newMerchant->getId());

        $response = $this->mockGetHostedResponseForPLWithExpiry();

        $bankingAccountMock = $this->mockBankingAccount(Channel::YESBANK);

        $this->enableRazorXTreatmentForKeylessHeader();

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest', 'getBankingAccountInfo', 'allowUpi', 'allowAmazonPay', 'getEnvironment'))
            ->getMock();
        $mock->method('makeRequest')->willReturn($response);
        $mock->method("getBankingAccountInfo")->willReturn($bankingAccountMock);
        $mock->method('allowUpi')->willReturn(false);
        $mock->method('allowAmazonPay')->willReturn(false);
        $mock->method('getEnvironment')->willReturn(Environment::TESTING);

        $data = $mock->getHostedPageData('poutlk_1000000000', $newMerchant);

        $this->assertArrayHasKey('payout_link_id', $data);

        $this->assertArrayHasKey('keyless_header', $data);

        $this->assertNotNull($data['keyless_header']);
    }

    public function testGetHostedPageForKeylessHeaderDisabledMerchant()
    {
        $newMerchant = $this->fixtures->create('merchant');

        $this->prepareBankingAccountData($newMerchant->getId());

        $response = $this->mockGetHostedResponseForPLWithExpiry();

        $bankingAccountMock = $this->mockBankingAccount(Channel::YESBANK);

        $this->disableRazorXTreatmentForKeylessHeader();

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest', 'getBankingAccountInfo', 'allowUpi', 'allowAmazonPay', 'getEnvironment'))
            ->getMock();
        $mock->method('makeRequest')->willReturn($response);
        $mock->method("getBankingAccountInfo")->willReturn($bankingAccountMock);
        $mock->method('allowUpi')->willReturn(false);
        $mock->method('allowAmazonPay')->willReturn(false);
        $mock->method('getEnvironment')->willReturn(Environment::TESTING);

        $data = $mock->getHostedPageData('poutlk_1000000000', $newMerchant);

        $this->assertArrayHasKey('payout_link_id', $data);

        $this->assertArrayHasKey('keyless_header', $data);

        $this->assertNull($data['keyless_header']);
    }

    protected function enableRazorXTreatmentForKeylessHeader()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment', 'getCachedTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->expects($this->any())->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === Merchant\RazorxTreatment::KEYLESS_HEADER_POUTLK)
                    {
                        return 'on';
                    }
                    return 'off';
                }));
    }

    protected function disableRazorXTreatmentForKeylessHeader()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment', 'getCachedTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->expects($this->any())->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === Merchant\RazorxTreatment::KEYLESS_HEADER_POUTLK)
                    {
                        return 'off';
                    }
                    return 'on';
                }));
    }

    protected static function getMethodWithModifiedAccessibility($name)
    {
        $class = new ReflectionClass('RZP\Services\PayoutLinks');

        $method = $class->getMethod($name);

        $method->setAccessible(true);

        return $method;
    }

    protected function mockRazorXTreatment($variant)
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn($variant);
    }

    public function testIsWorkflowEnabledForBlacklistedMerchant()
    {
        // for blacklisted merchant, the existing flag will be not enabled i.e. variant will be 'off'
        // (for the merchant for which the flag is disabled, workflow-feature is disabled)
        $this->mockRazorXTreatment('off');

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->getMock();

        $workflowCheckMethod = self::getMethodWithModifiedAccessibility('isWorkflowEnabledForPLMerchant');

        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('feature', [
            'name'          => 'payout_workflows',
            'entity_id'     => $merchant->getId(),
            'entity_type'   => 'merchant',
        ]);

        $result = $workflowCheckMethod->invokeArgs($mock, [$merchant]);

        $this->assertEquals(false, $result);
    }

    public function testIsWorkflowEnabledForGeneralMerchant()
    {
        // for general merchant, the existing flag will be enabled
        // (i.e. for the merchant for which the flag is enabled, workflow-feature is enabled)
        $this->mockRazorXTreatment('on');

        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->getMock();

        $workflowCheckMethod = self::getMethodWithModifiedAccessibility('isWorkflowEnabledForPLMerchant');

        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('feature', [
            'name'          => 'payout_workflows',
            'entity_id'     => $merchant->getId(),
            'entity_type'   => 'merchant',
        ]);

        $result = $workflowCheckMethod->invokeArgs($mock, [$merchant]);

        $this->assertEquals(true, $result);
    }

    public function testPayoutWorkflowsFeaturesDisabledPL()
    {
        $mock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->getMock();

        $workflowCheckMethod = self::getMethodWithModifiedAccessibility('isWorkflowEnabledForPLMerchant');

        $merchant = $this->fixtures->create('merchant');

        $result = $workflowCheckMethod->invokeArgs($mock, [$merchant]);

        $this->assertEquals(false, $result);
    }
}

