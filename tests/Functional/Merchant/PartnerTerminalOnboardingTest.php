<?php

namespace RZP\Tests\Functional\Merchant;

use Mockery;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Terminal\Repository as TerminalRepo;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Fixtures\Entity\Terminal as TerminalFixture;
use RZP\Tests\Functional\Mpan\MpanTrait;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Models\Feature\Constants as FeatureConstants;

class PartnerTerminalOnboardingTest extends TestCase
{
    use PaymentTrait;
    use PartnerTrait;
    use MpanTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PartnerTerminalOnboardingTestData.php';

        parent::setUp();
    }

    public function testEnableTerminal()
    {
        $this->app['config']->set('gateway.mock_mozart', true);

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $terminal = $this->fixtures->create('terminal', [
            'enabled'     => false,
            'status'      => 'deactivated',
            'merchant_id' => $subMerchantId,
            'gateway'     => 'worldline',
            'mc_mpan'     => base64_encode('1234567890123456'),
            'visa_mpan'   => base64_encode('9876543210123456'),
            'rupay_mpan'  => base64_encode('1234123412341234'),
            'notes'       => 'some notes'
        ]);

        $url = '/terminals/' . $terminal->getSignedId($terminal['id']) . '/enable';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $terminal->reload();

        $this->assertEquals($terminal['enabled'], true);

        $this->assertEquals($terminal['status'], 'activated');
    }

    public function testEnableTerminalFailedOnGateway()
    {
        $this->app['config']->set('gateway.mock_mozart', true);

        $this->app['config']->set('worldline_terminal_onboarding_enable.case', "2");

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $terminal = $this->fixtures->create('terminal', [
            'enabled'     => false,
            'status'      => 'deactivated',
            'merchant_id' => $subMerchantId,
            'gateway'     => 'worldline',
            'mc_mpan'     => base64_encode('1234567890123456'),
            'visa_mpan'   => base64_encode('9876543210123456'),
            'rupay_mpan'  => base64_encode('1234123412341234'),
            'notes'       => 'some notes'
        ]);
        
        $url = '/terminals/' . $terminal->getSignedId($terminal['id']) . '/enable';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $terminal->reload();
        
        $this->assertEquals($terminal['enabled'], false);
    }

    public function testEnableTerminalAlreadyEnabledOnGateway()
    {
        $this->app['config']->set('gateway.mock_mozart', true);

        $this->app['config']->set('worldline_terminal_onboarding_enable.case', "3");

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $terminal = $this->fixtures->create('terminal', [
            'enabled'     => false,
            'status'      => 'deactivated',
            'merchant_id' => $subMerchantId,
            'gateway'     => 'worldline',
            'mc_mpan'     => base64_encode('1234567890123456'),
            'visa_mpan'   => base64_encode('9876543210123456'),
            'rupay_mpan'  => base64_encode('1234123412341234'),
            'notes'       => 'some notes'
        ]);

        $url = '/terminals/' . $terminal->getSignedId($terminal['id']) . '/enable';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $terminal->reload();

        $this->assertEquals($terminal['enabled'], true);
    }

    public function testDisableTerminal()
    {
        $this->app['config']->set('gateway.mock_mozart', true);

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $terminal = $this->fixtures->create('terminal', [
            'enabled'     => true,
            'status'      => 'activated',
            'merchant_id' => $subMerchantId,
            'gateway'     => 'worldline',
            'mc_mpan'     => base64_encode('1234567890123456'),
            'visa_mpan'   => base64_encode('9876543210123456'),
            'rupay_mpan'  => base64_encode('1234123412341234'),
            'notes'       => 'some notes'
        ]);
        
        $url = '/terminals/' . $terminal->getSignedId($terminal['id']) . '/disable';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
        
        $terminal->reload();

        $this->assertEquals($terminal['enabled'], false);   

        $this->assertEquals($terminal['status'], 'deactivated');
    }

    // FC can disable pending or activated terminal
    public function testDisablePendingTerminal()
    {
        $this->app['config']->set('gateway.mock_mozart', true);

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $terminal = $this->fixtures->create('terminal', [
            'enabled'     => true,
            'status'      => 'pending',
            'merchant_id' => $subMerchantId,
            'gateway'     => 'worldline',
            'mc_mpan'     => base64_encode('1234567890123456'),
            'visa_mpan'   => base64_encode('9876543210123456'),
            'rupay_mpan'  => base64_encode('1234123412341234'),
            'notes'       => 'some notes'
        ]);
        
        $url = '/terminals/' . $terminal->getSignedId($terminal['id']) . '/disable';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
        
        $terminal->reload();

        $this->assertEquals($terminal['enabled'], false);   

        $this->assertEquals($terminal['status'], 'deactivated');
    }

    public function testDisableTerminalFailedOnGateway()
    {
        $this->app['config']->set('gateway.mock_mozart', true);

        $this->app['config']->set('worldline_terminal_onboarding_disable.case', "2");

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $terminal = $this->fixtures->create('terminal', [
            'enabled'     => true,
            'status'      => 'activated',
            'merchant_id' => $subMerchantId,
            'gateway'     => 'worldline',
            'mc_mpan'     => base64_encode('1234567890123456'),
            'visa_mpan'   => base64_encode('9876543210123456'),
            'rupay_mpan'  => base64_encode('1234123412341234'),
            'notes'       => 'some notes'
        ]);

        $url = '/terminals/' . $terminal->getSignedId($terminal['id']) . '/disable';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $terminal->reload();

        $this->assertEquals($terminal['enabled'], true);

        $this->assertEquals($terminal['status'], 'activated');
    }

    public function testDisableTerminalAlreadyDisabledOnGateway()
    {
        $this->app['config']->set('gateway.mock_mozart', true);

        $this->app['config']->set('worldline_terminal_onboarding_disable.case', "3");

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $terminal = $this->fixtures->create('terminal', [
            'enabled'     => true,
            'status'      => 'activated',
            'merchant_id' => $subMerchantId,
            'gateway'     => 'worldline',
            'mc_mpan'     => base64_encode('1234567890123456'),
            'visa_mpan'   => base64_encode('9876543210123456'),
            'rupay_mpan'  => base64_encode('1234123412341234'),
            'notes'       => 'some notes'
        ]);

        $url = '/terminals/' . $terminal->getSignedId($terminal['id']) . '/disable';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $terminal->reload();

        $this->assertEquals($terminal['enabled'], false);
    }

    public function testOnlyDeactivatedTerminalsShouldBeEnabled()
    {
        $this->app['config']->set('gateway.mock_mozart', true);

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $terminal = $this->fixtures->create('terminal', [
            'enabled'     => false,
            'status'      => 'pending',
            'gateway'     => 'worldline',
            'merchant_id' => $subMerchantId,
            'mc_mpan'     => base64_encode('1234567890123456'),
            'visa_mpan'   => base64_encode('9876543210123456'),
            'rupay_mpan'  => base64_encode('1234123412341234'),
            'notes'       => 'some notes'
        ]);

        $url = '/terminals/' . $terminal->getSignedId($terminal['id']) . '/enable';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testSubMerchantsShouldNotBeAbleToDisableTerminals()
    {
        $this->ba->privateAuth();

        $terminal = $this->fixtures->create('terminal', [
            'enabled'     => true,
            'merchant_id' => '10000000000000',
            'mc_mpan'     => base64_encode('1234567890123456'),
            'visa_mpan'   => base64_encode('9876543210123456'),
            'rupay_mpan'  => base64_encode('1234123412341234'),
            'notes'       => 'some notes'
        ]);

        $url = '/terminals/' . $terminal->getSignedId($terminal['id']) . '/disable';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testFetchTerminals()
    {
        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $url = '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->fixtures->create('terminal', [
            'enabled'     => true,
            'status'      => 'activated',
            'merchant_id' => $subMerchantId,
            'mc_mpan'     => base64_encode('4287346823986423'),
            'visa_mpan'   => base64_encode('5287346823986423'),
            'rupay_mpan'  => base64_encode('6287346823986423'),
        ]);

            $this->fixtures->create('terminal', [
            'enabled'     => false,
            'status'      => 'pending',
            'merchant_id' => $subMerchantId,
            'mc_mpan'     => base64_encode('5220240401208405'),
            'visa_mpan'   => base64_encode('4403844012084006'),
            'rupay_mpan'  => base64_encode('6100030401208403'),
        ]);


        $this->startTest();

        $this->assertPassport();

        $this->assertPassportKeyExists('impersonation.consumer.id', "/^{$subMerchantId}$/");
        $this->assertPassportKeyExists('credential.username', "/^rzp_test_partner_[a-zA-Z0-9]{14}$/");
        $this->assertPassportKeyExists('credential.public_key', "/^rzp_test_partner_[a-zA-Z0-9]{14}-acc_{$subMerchantId}$/");
    }

    public function testPartnerWithoutTerminalOnboardingFeatureShouldNotBeAbleToFetchTerminals()
    {
        $this->setUpPartnerAuthAndGetSubMerchantId();

        $url = '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testSubMerchantsShouldNotBeAbleToFetchTerminals()
    {
        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/terminals';

        $this->startTest();
    }

    public function testTerminalOnboardingCreateTerminal()
    {
        $this->setUpMpans('10000000000000');   

        $this->app['config']->set('gateway.mock_mozart', true);

        $this->setUpTidConfigs();

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $url = '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        // happy flow
        $terminalArray = $this->startTest();

        $tid = $terminalArray['id'];

        $this->fixtures->stripSign($tid);

        $terminal1 = (new Terminal\Repository)->find($tid);

        $this->assertEquals($terminal1->getGatewayMerchantId(), 999000000000001);
        $this->assertEquals($terminal1->getGatewayTerminalId(), 12380001);
        $this->assertEquals($terminal1->getMcMpan(), base64_encode('5122600005005789'));
        $this->assertEquals($terminal1->getVisaMpan(), base64_encode('4604901005005799'));
        $this->assertEquals($terminal1->getRupayMpan(), base64_encode('6100020005005792'));

        $subMerchant = (new Merchant\Repository)->find($subMerchantId);

        $this->assertTrue($subMerchant->isFeatureEnabled('bharat_qr'));

        // additional tid flow
        $this->app['config']->set('worldline_terminal_onboarding_creation.case', "8");

        $this->testData[__FUNCTION__] = $this->testData['testTerminalOnboardingCreateTerminalAdditionalTidFlow'];

        $this->startTest();
    }

    public function testTerminalOnboardingCreateTerminalWithBarredMcc()
    {
        $this->setUpMpans('10000000000000');

        $this->app['config']->set('gateway.mock_mozart', true);

        $this->setUpTidConfigs();

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId(true, 5399);

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $url = '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testTerminalOnboardingCreateTerminalForNonActivatedMerchant()
    {
        $this->app['config']->set('gateway.mock_mozart', true);

        $this->ba->adminAuth();

        $this->setUpPartnerAuthAndGetSubMerchantId($activated = false);

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $url = '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    // If we try to reuse same mpans, request should not go to gateway and should fail in checkDbConstraints method
    public function testTerminalOnboardingCreateTerminalWithSameFields()
    {
        $this->setUpMpans('10000000000000');

        $this->app['config']->set('gateway.mock_mozart', true);

        $this->app['config']->set('worldline_terminal_onboarding_creation.case', "7");

        $this->setUpTidConfigs();

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        // create a pending terminal with same mpans as to be used in request
        $terminal = $this->fixtures->create('terminal', [
            'enabled'     => true,
            'status'      => 'pending',
            'merchant_id' => $subMerchantId,
            'mc_mpan'     => base64_encode('5122600005005789'),
            'visa_mpan'   => base64_encode('4604901005005799'),
            'rupay_mpan'  => base64_encode('6100020005005792'),
            'notes'       => 'some notes'
        ]);

        $url = '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->testData[__FUNCTION__]['response']['content']['error']['description'] = 'A terminal with the same field exists - ' . $terminal->getId();

        // checkDbConstraints method would raise error and terminal creation request should not go to gateway
        $this->startTest();        
    }

    // We should be able to create terminal with same fields if existing terminal is failed, but not if it is pending
    public function testTerminalOnboardingCreateTerminalWithSameFieldsExistingTerminalIsFailed()
    {
        $this->setUpMpans('10000000000000');

        $this->app['config']->set('gateway.mock_mozart', true);

        $this->setUpTidConfigs();

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        // create a failed terminal with same mpans as to be used in request
        $this->fixtures->create('terminal', [
            'enabled'     => true,
            'status'      => 'failed',
            'merchant_id' => $subMerchantId,
            'mc_mpan'     => base64_encode('5122600005005789'),
            'visa_mpan'   => base64_encode('4604901005005799'),
            'rupay_mpan'  => base64_encode('6100020005005792'),
            'notes'       => 'some notes'
        ]);

        $url = '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        // terminal should get created in this request as earlier terminal with same mpan is in failed state
        $this->startTest();        
    }

    public function testTerminalOnboardingCreateTerminalWithMpansNotIssued()
    {
        $this->setUpTidConfigs();

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $this->startTest();
    }

    public function testTerminalOnboardingCreateTerminalWithSwappedNetworks()
    {
        $this->setUpMpans('10000000000000');

        $this->setUpTidConfigs();

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $this->startTest();
    }

    // Same network having more than one used network
    public function testTerminalOnboardingCreateTerminalWithSwappedNetworks2()
    {
        $this->setUpMpans('10000000000000');

        $this->setUpTidConfigs();

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $this->startTest();
    }

    // Validation failure by Mozart
    public function testTerminalOnboardingCreateTerminalCase2()
    {
        $this->setUpMpans('10000000000000');   

        $this->app['config']->set('gateway.mock_mozart', true);

        $this->app['config']->set('worldline_terminal_onboarding_creation.case', "2");

        $this->setUpTidConfigs();

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $url = '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }  
    
    // Error from gateway
    public function testTerminalOnboardingCreateTerminalCase3()
    {
        $this->setUpMpans('10000000000000');   

        $this->app['config']->set('gateway.mock_mozart', true);

        $this->app['config']->set('worldline_terminal_onboarding_creation.case', "3");

        $this->setUpTidConfigs();

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $url = '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testTerminalOnboardingCreateTerminalCase4()
    {
        $this->setUpMpans('10000000000000');   

        $this->app['config']->set('gateway.mock_mozart', true);

        $this->app['config']->set('worldline_terminal_onboarding_creation.case', "4");

        $this->setUpTidConfigs();

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $url = '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }
    
    // Invalid Mpan error from gateway
    public function testTerminalOnboardingCreateTerminalCase5()
    {
        $this->setUpMpans('10000000000000');   

        $this->app['config']->set('gateway.mock_mozart', true);

        $this->app['config']->set('worldline_terminal_onboarding_creation.case', "5");

        $this->setUpTidConfigs();

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $url = '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    // E.g. error raised by app/Gateway/Base 's sendExternalRequest() method (see validateResponse() of the same base class)
    public function testTerminalOnboardingCreateTerminalGatewayErrorByMozart()
    {
        $this->setUpMpans('10000000000000');   

        $this->app['config']->set('gateway.mock_mozart', true);

        $this->app['config']->set('worldline_terminal_onboarding_creation.case', "9");

        $this->setUpTidConfigs();

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $url = '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    /**
     * functional test for terminal repo's findEnabledTerminalByMpanAndGatewayMerchantId()
     * which is used to fetch terminals for worldline BQR push payments
     */
    public function testFindEnabledTerminalByMpanAndGatewayMerchantId()
    {
        $terminal = $this->fixtures->create('terminal', [
            'enabled'             => true,
            'gateway'             => 'worldline',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000002',
            'mc_mpan'             => '1234567890123456',
            'visa_mpan'           => '9876543210123456',
            'rupay_mpan'          => '1234123412341234',
            'notes'               => 'some notes'
        ]);

        $fetchedTerminal = (new TerminalRepo)->findEnabledTerminalByMpanAndGatewayMerchantId('90000000002', 'worldline', '1234567890123456');

        $this->assertEquals($fetchedTerminal->getId(), $terminal['id']); 
    }

    public function testFindEnabledTerminalByMpanAndGatewayMerchantIdNonEnabledTerminal()
    {
        $this->fixtures->create('terminal', [
            'enabled'             => false,
            'gateway'             => 'worldline',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000002',
            'status'              => 'activated',
            'mc_mpan'             => '1234567890123456',
            'visa_mpan'           => '9876543210123456',
            'rupay_mpan'          => '1234123412341234',
            'notes'               => 'some notes'
        ]);

        $fetchedTerminal = (new TerminalRepo)->findEnabledTerminalByMpanAndGatewayMerchantId('90000000002', 'worldline', '1234567890123456');

        $this->assertEmpty($fetchedTerminal); 
    }

    public function testFindEnabledTerminalByMpanAndGatewayMerchantIdPendingEnabledTerminal()
    {
        $this->fixtures->create('terminal', [
            'enabled'             => false,
            'gateway'             => 'worldline',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000002',
            'status'              => 'pending',
            'mc_mpan'             => '1234567890123456',
            'visa_mpan'           => '9876543210123456',
            'rupay_mpan'          => '1234123412341234',
            'notes'               => 'some notes'
        ]);

        $fetchedTerminal = (new TerminalRepo)->findEnabledTerminalByMpanAndGatewayMerchantId('90000000002', 'worldline', '1234567890123456');

        $this->assertEmpty($fetchedTerminal); 
    }

    protected function mockGateway()
    {
        $gateway = Mockery::mock('RZP\Gateway\GatewayManager');

        $gateway->shouldReceive('call')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'),
            Mockery::type('string'), Mockery::type('RZP\Models\Terminal\Entity'))
            ->andReturnUsing
            (function ($gateway,$action,$input,$mode)
            {
                $length = count($input);

                $this->assertEquals(6, $length);

                $responseBody = [
                    'data' => [
                        'description'   => "Success",
                        'res_code'        => "00",
                        '_raw'          => "{\"TID\":\"9137251R\",\"REQRRN\":null,\"RESDTTM\":\"23082019134719\",\"RESCODE\":\"00\",\"RESDESC\":\"Success\",\"REQTYPE\":\"N\",\"BANKCODE\":\"00031\",\"MID\":\"999122000040351\"}"
                    ],
                    'error'             => [],
                    'external_trace_id' => "",
                    'mozart_id'         => "blfq216r1gunssphbs01",
                    'next'              => null,
                    'success'           => true
                ];

                return $responseBody;
            });

        $this->app->instance('gateway', $gateway);
    }

    protected function setUpTidConfigs()
    {
        $this->ba->adminAuth();

        $request = [
            'method'  => 'put',
            'url'     => '/config/keys',
            'content' => [
                'config:atos_tid_range_list' => [ [12380001, 123899999], [13380001, 13389999]]
            ]
        ];

        $this->makeRequestAndGetContent($request);
    }
    
    private function setUpMpans($merchantId)
    {
        $mpanData = $this->getMpanData();

        foreach ($mpanData as $mpan)
        {
            $mpan['merchant_id'] = $merchantId;

            $mpan['mpan'] = base64_encode($mpan['mpan']);

            $this->fixtures->create('mpan', $mpan);
        }
    }

}
