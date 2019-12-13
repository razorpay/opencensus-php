<?php

namespace RZP\Tests\Functional\Merchant;

use Carbon\Carbon;
use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\Redis;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Gateway\Terminal\GatewayProcessor\Worldline;
use RZP\Tests\Functional\Fixtures\Entity\Base as BaseFixture;
use RZP\Tests\Functional\Fixtures\Entity\Terminal as TerminalFixture;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Exception\BadRequestException;

class PartnerTerminalOnboardingTest extends TestCase
{
    use PaymentTrait;
    use PartnerTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/TerminalData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testEnableTerminal()
    {
        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $terminal = $this->fixtures->create('terminal', [
            'enabled'     => false,
            'status'      => 'activated',
            'merchant_id' => $subMerchantId,
            'mc_mpan'     => '1234567890123456',
            'visa_mpan'   => '9876543210123456',
            'rupay_mpan'  => '1234123412341234',
            'notes'       => 'some notes'
        ]);

        $url = '/terminals/' . $terminal->getSignedId($terminal['id']) . '/enable';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testDisableTerminal()
    {
        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $terminal = $this->fixtures->create('terminal', [
            'enabled'     => true,
            'merchant_id' => $subMerchantId,
            'mc_mpan'     => '1234567890123456',
            'visa_mpan'   => '9876543210123456',
            'rupay_mpan'  => '1234123412341234',
            'notes'       => 'some notes'
        ]);

        $url = '/terminals/' . $terminal->getSignedId($terminal['id']) . '/disable';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testOnlyActivatedTerminalShouldBeEnabled()
    {
        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $terminal = $this->fixtures->create('terminal', [
            'enabled'     => true,
            'status'      => 'pending',
            'merchant_id' => $subMerchantId,
            'mc_mpan'     => '1234567890123456',
            'visa_mpan'   => '9876543210123456',
            'rupay_mpan'  => '1234123412341234',
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
            'mc_mpan'     => '1234567890123456',
            'visa_mpan'   => '9876543210123456',
            'rupay_mpan'  => '1234123412341234',
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

        $terminal1 = (new TerminalFixture)->createBharatQrTerminal();

        $terminal1['merchant_id'] = $subMerchantId;

        $terminal1->save();

        $terminal2 = (new TerminalFixture)->createBharatQrIsgTerminal();

        $terminal2['merchant_id'] = $subMerchantId;

        $terminal2->save();

        $this->startTest();
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
        $this->app['config']->set('gateway.mock_mozart', true);

        $this->ba->adminAuth();

        $request = [
            'method'  => 'put',
            'url'     => '/config/keys',
            'content' => [
                'config:atos_tid_range_list' => [ [12380001, 123899999], [13380001, 13389999]]
            ]
        ];
        $this->makeRequestAndGetContent($request);

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $url = '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $terminalArray = $this->startTest();

        $tid = $terminalArray['id'];

        $this->fixtures->stripSign($tid);

        $terminal1 = (new Terminal\Repository)->find($tid);

        $this->assertEquals($terminal1->getGatewayMerchantId(), 999000000000001);

        $this->assertEquals($terminal1->getGatewayTerminalId(), 12380001);

        $this->testData[__FUNCTION__] = $this->testData['testTerminalOnboardingCreateTerminal2'];

        $terminalArray = $this->startTest();

        $tid = $terminalArray['id'];

        $this->fixtures->stripSign($tid);

        $terminal2 = (new Terminal\Repository)->find($tid);

        $this->assertEquals($terminal2->getGatewayMerchantId(), 999000000000001);

        $this->assertEquals($terminal2->getGatewayTerminalId(), 12380002);

        $this->expectException(BadRequestException::class);

        $this->expectExceptionCode(
            ErrorCode::BAD_REQUEST_FIELD_ALREADY_EXISTS);

        $this->expectExceptionMessage(
            'A terminal with the same field exists');

        $this->startTest();
    }

    // We should be able to create terminal with same fields if existing terminal is failed
    public function testTerminalOnboardingCreateTerminalWithSameFields()
    {
        $this->app['config']->set('gateway.mock_mozart', true);

        $this->ba->adminAuth();

        $request = [
            'method'  => 'put',
            'url'     => '/config/keys',
            'content' => [
                'config:atos_tid_range_list' => [ [12380001, 123899999], [13380001, 13389999]]
            ]
        ];
        $this->makeRequestAndGetContent($request);

        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $terminal = $this->fixtures->create('terminal', [
            'category'          => '5399',
            'merchant_id'       => $subMerchantId,
            'enabled'           => false,
            'gateway'           => 'worldline',
            'gateway_acquirer'  => null,
            'mc_mpan'           => '1234567880123456',
            'visa_mpan'         => '1234567890123456',
            'rupay_mpan'        => '1234567890123457',
            'status'            => 'failed',
            'type'              => [
                                    'non_recurring'=> '1',
                                    'bharat_qr'=> '1',
                                    ]
        ]);

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $url = '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $this->expectException(BadRequestException::class);

        $this->expectExceptionCode(
            ErrorCode::BAD_REQUEST_FIELD_ALREADY_EXISTS);

        $this->expectExceptionMessage(
            'A terminal with the same field exists');

        $this->startTest();
    }

    public function testTerminalOnboardingVerificationCronCase1()
    {
        $this->app['config']->set('gateway.mock_mozart', true);

        $this->app['config']->set('worldline_terminal_onboarding_verification.case', "1");

        $this->ba->cronAuth();

        $terminal = $this->fixtures->create('terminal', [
            'enabled'     => false,
            'gateway'     => 'worldline',
            'status'      => 'pending'
        ]);

        $merchant = $terminal->merchant;
        $merchant->setCategory("742");
        $merchant->save();

        $activationTime = Carbon::now()->subMinutes(10);

        $terminalOnboardingDetail = $this->fixtures->create('terminal_onboarding_detail', [
            'terminal_id'       => $terminal->getId(),
            'status'            => 'pending',
            'verify_bucket'     => 0,
            'verify_at'         => $activationTime->getTimestamp(),
        ]);

        $this->startTest();

        $updatedTerminalOnboardingDetail = $this->getEntityById(
            'terminal_onboarding_detail',
            $terminalOnboardingDetail->getId(),
            true
        );

        $terminal->reload();

        $this->assertEquals($terminal['status'], 'activated');

        $this->assertEquals($updatedTerminalOnboardingDetail['status'], 'activated');
        $this->assertNull($updatedTerminalOnboardingDetail['verify_at']);
    }

    // Failure case
    public function testTerminalOnboardingVerificationCronCase2()
    {
        $this->app['config']->set('gateway.mock_mozart', true);

        $this->app['config']->set('worldline_terminal_onboarding_verification.case', "2");

        $this->ba->cronAuth();

        $terminal = $this->fixtures->create('terminal', [
            'enabled'     => false,
            'gateway'     => 'worldline',
            'status'      => 'pending'
        ]);

        $merchant = $terminal->merchant;
        $merchant->setCategory("742");
        $merchant->save();

        $activationTime = Carbon::now()->subMinutes(10);

        $terminalOnboardingDetail = $this->fixtures->create('terminal_onboarding_detail', [
            'terminal_id'       => $terminal->getId(),
            'status'            => 'pending',
            'verify_bucket'     => 0,
            'verify_at'         => $activationTime->getTimestamp(),
        ]);

        $this->startTest();

        $updatedTerminalOnboardingDetail = $this->getEntityById(
            'terminal_onboarding_detail',
            $terminalOnboardingDetail->getId(),
            true
        );

        $terminal->reload();

        $this->assertEquals($terminal['status'], 'pending');

        $this->assertEquals($updatedTerminalOnboardingDetail['status'], 'pending');
    }

    // Failure case with exhausted retry
    public function testTerminalOnboardingVerificationCronCase3()
    {
        $this->app['config']->set('gateway.mock_mozart', true);

        $this->app['config']->set('worldline_terminal_onboarding_verification.case', "2");

        $this->ba->cronAuth();

        $terminal = $this->fixtures->create('terminal', [
            'enabled'     => false,
            'gateway'     => 'worldline',
            'status'      => 'pending'
        ]);

        $merchant = $terminal->merchant;
        $merchant->setCategory("742");
        $merchant->save();

        $activationTime = Carbon::now()->subMinutes(10);

        $terminalOnboardingDetail = $this->fixtures->create('terminal_onboarding_detail', [
            'terminal_id'       => $terminal->getId(),
            'status'            => 'pending',
            'verify_bucket'     => 9,
            'verify_at'         => $activationTime->getTimestamp(),
        ]);

        $this->startTest();

        $updatedTerminalOnboardingDetail = $this->getEntityById(
            'terminal_onboarding_detail',
            $terminalOnboardingDetail->getId(),
            true
        );

        $terminal->reload();

        $this->assertEquals($terminal['status'], 'failed');

        $this->assertEquals($updatedTerminalOnboardingDetail['status'], 'activation_failed');
    }

    // Note: we need different unit tests for each case, so that terminalOnboardingDetail don't get mixed
    // Success case
    public function testTerminalOnboardingCreationCronCase1()
    {
        $this->app['config']->set('gateway.mock_mozart', true);

        $this->app['config']->set('worldline_terminal_onboarding_creation.case', "1");

        $this->ba->cronAuth();

        $subMerchant = $this->fixtures->create('merchant');

        $subMerchantId = $subMerchant->getId();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        // Assign submerchant to partner
        $accessMapData = [
            'entity_type'     => 'application',
            'merchant_id'     => $subMerchantId,
            'entity_owner_id' => '10000000000000',
        ];

        $this->fixtures->create('merchant_access_map', $accessMapData);

        $terminal = $this->fixtures->create('terminal', [
            'merchant_id'       => $subMerchantId,
            'enabled'           => false,
            'gateway'           => 'worldline',
            'account_number'    => '10010101011',
            'ifsc_code'         => 'RZPB0000000',
            'status'            => 'created'
            ]);

        $terminalOnboardingDetail = $this->fixtures->create('terminal_onboarding_detail', [
            'terminal_id'       => $terminal->getId(),
            'status'            => 'created',
            'attempts'          => 0,
            'verify_bucket'     => 0,
        ]);

        $merchant = $terminal->merchant;

        $merchant->setCategory("742");

        $merchant->save();

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => $subMerchantId,
                'submitted'   => true,
                'business_registered_state' => 'MH',
                'locked'      => true
            ]);

        (new BaseFixture)->createEntityInTestAndLive('merchant_detail', [
            'merchant_id' => '10000000000000',
            'submitted'   => true,
            'business_registered_state' => 'KA',
            'locked'      => true
        ]);
         
        $url = '/terminals/onboard/creation';

        $this->testData[__FUNCTION__]  =  $this->testData['testTerminalOnboardingCreationCron'];

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $onboardedTerminals = $this->startTest();

        $this->assertEquals(count($onboardedTerminals['terminal_ids_fetched']), 1);

        $this->assertEquals(count($onboardedTerminals['terminal_ids_queued']), 1);

        $terminal->reload();

        $terminalOnboardingDetail->reload();

        $updatedTerminalOnboardingDetail = $this->getEntityById(
            'terminal_onboarding_detail',
            $terminalOnboardingDetail->getId(),
            true
        );
        
        $this->assertEquals($terminal->getStatus(), 'pending');

        // $this->assertEquals($terminalOnboardingDetail['status'], 'pending');
    }

    // Validation failure by Mozart
    public function testTerminalOnboardingCreationCronCase2()
    {
        $this->app['config']->set('gateway.mock_mozart', true);

        $this->app['config']->set('worldline_terminal_onboarding_creation.case', "2");

        $this->ba->cronAuth();

        list($terminal, $terminalOnboardingDetail) = $this->setUpTerminalOnboardingFailureCases();

        $url = '/terminals/onboard/creation';

        $this->testData[__FUNCTION__] = $this->testData['testTerminalOnboardingCreationCron'];

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $onboardedTerminals = $this->startTest();

        $this->assertEquals(count($onboardedTerminals['terminal_ids_fetched']), 1);

        $this->assertEquals(count($onboardedTerminals['terminal_ids_queued']), 1);
        
        $terminalOnboardingDetail->reload();

        $updatedTerminalOnboardingDetail = $this->getEntityById(
            'terminal_onboarding_detail',
            $terminalOnboardingDetail->getId(),
            true
        );

        $terminal->reload();
        
        $this->assertEquals($terminal->getStatus(), 'failed');

        // $this->assertEquals($updatedTerminalOnboardingDetail['status'], 'failed');        
    }

    // Error from Worldline Gateway
    public function testTerminalOnboardingCreationCronCase3()
    {
        $this->app['config']->set('gateway.mock_mozart', true);

        $this->app['config']->set('worldline_terminal_onboarding_creation.case', "3");

        $this->ba->cronAuth();

        list($terminal, $terminalOnboardingDetail) = $this->setUpTerminalOnboardingFailureCases();

        $url = '/terminals/onboard/creation';

        $this->testData[__FUNCTION__] = $this->testData['testTerminalOnboardingCreationCron'];

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $onboardedTerminals = $this->startTest();

        $this->assertEquals(count($onboardedTerminals['terminal_ids_fetched']), 1);

        $this->assertEquals(count($onboardedTerminals['terminal_ids_queued']), 1);
        
        $terminalOnboardingDetail->reload();

        $updatedTerminalOnboardingDetail = $this->getEntityById(
            'terminal_onboarding_detail',
            $terminalOnboardingDetail->getId(),
            true
        );

        $terminal->reload();

        $this->assertEquals($terminal->getStatus(), 'failed');

        // $this->assertEquals($updatedTerminalOnboardingDetail['status'], 'failed');        

        $this->assertEquals($updatedTerminalOnboardingDetail['error_description'], 'Invalid Terminal ID');
    }

    // Internal Razorpay Error. E.g. mozart route not found / Mozart 502
    public function testTerminalOnboardingCreationCronCase4()
    {
        $this->app['config']->set('gateway.mock_mozart', true);

        $this->app['config']->set('worldline_terminal_onboarding_creation.case', "4");

        $this->ba->cronAuth();

        list($terminal, $terminalOnboardingDetail) = $this->setUpTerminalOnboardingFailureCases();

        $url = '/terminals/onboard/creation';

        $this->testData[__FUNCTION__] = $this->testData['testTerminalOnboardingCreationCron'];

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $onboardedTerminals = $this->startTest();

        $this->assertEquals(count($onboardedTerminals['terminal_ids_fetched']), 1);

        $this->assertEquals(count($onboardedTerminals['terminal_ids_queued']), 1);
        
        $terminalOnboardingDetail->reload();

        $updatedTerminalOnboardingDetail = $this->getEntityById(
            'terminal_onboarding_detail',
            $terminalOnboardingDetail->getId(),
            true
        );

        $terminal->reload();
        
        $this->assertEquals($terminal->getStatus(), 'failed');

        // $this->assertEquals($updatedTerminalOnboardingDetail['status'], 'failed');        

        $this->assertEquals($updatedTerminalOnboardingDetail['error_description'], 'Invalid route');
    }

    // Duplicate mpan error from Worldline
    public function testTerminalOnboardingCreationCronCase5()
    {
        $this->app['config']->set('gateway.mock_mozart', true);

        $this->app['config']->set('worldline_terminal_onboarding_creation.case', "5");

        $this->ba->cronAuth();

        list($terminal, $terminalOnboardingDetail) = $this->setUpTerminalOnboardingFailureCases();

        $url = '/terminals/onboard/creation';

        $this->testData[__FUNCTION__] = $this->testData['testTerminalOnboardingCreationCron'];

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $onboardedTerminals = $this->startTest();

        $this->assertEquals(count($onboardedTerminals['terminal_ids_fetched']), 1);

        $this->assertEquals(count($onboardedTerminals['terminal_ids_queued']), 1);
        
        $terminalOnboardingDetail->reload();

        $updatedTerminalOnboardingDetail = $this->getEntityById(
            'terminal_onboarding_detail',
            $terminalOnboardingDetail->getId(),
            true
        );

        $terminal->reload();

        $this->assertEquals($terminal->getStatus(), 'failed');

        // $this->assertEquals($updatedTerminalOnboardingDetail['status'], 'failed');        

        $this->assertEquals($updatedTerminalOnboardingDetail['error_code'], 'GATEWAY_ERROR_INVALID_DATA');

        $this->assertEquals($updatedTerminalOnboardingDetail['error_description'], 'Duplicate MVISAPAN');
    }

    public function testUpdateTerminalOnboardingStatus()
    {
        $this->ba->adminAuth();

        $terminal = $this->fixtures->create('terminal', [
            'status'    =>  'created',
        ]);

        $terminalOnboardingDetail = $this->fixtures->create('terminal_onboarding_detail', [
            'terminal_id'       => $terminal->getId(),
            'status'            => 'queued',
            'attempts'          => 0,
            'verify_bucket'     => 0,
        ]);

        $terminal2 = $this->fixtures->create('terminal', [
            'status'    =>  'failed',
        ]);

        $terminalOnboardingDetail2 = $this->fixtures->create('terminal_onboarding_detail', [
            'terminal_id'       => $terminal2->getId(),
            'status'            => 'failed',
            'attempts'          => 0,
            'verify_bucket'     => 0,
        ]);
        
        $this->testData[__FUNCTION__]['request']['content'] = [$terminalOnboardingDetail['id'], $terminalOnboardingDetail2['id']];

        $response = $this->startTest();

        $this->assertEquals($response['updated_terminal_onboarding_ids'], [$terminalOnboardingDetail['id']] );

        $this->assertEquals($response['not_applicable_terminal_onboarding_ids'], [$terminalOnboardingDetail2['id']] );

        $updatedTerminal = $this->getEntityById(
            'terminal',
            $terminal->getId(),
            true
        );

        $updatedTerminalOnboardingDetail = $this->getEntityById(
            'terminal_onboarding_detail',
            $terminalOnboardingDetail->getId(),
            true
        );

        $this->assertEquals($updatedTerminal['status'], 'created');

        $this->assertEquals($updatedTerminalOnboardingDetail['status'], 'created');

        $updatedTerminal2 = $this->getEntityById(
            'terminal',
            $terminal2->getId(),
            true
        );

        $updatedTerminalOnboardingDetail2 = $this->getEntityById(
            'terminal_onboarding_detail',
            $terminalOnboardingDetail2->getId(),
            true
        );

        $this->assertEquals($updatedTerminal2['status'], 'failed');

        $this->assertEquals($updatedTerminalOnboardingDetail2['status'], 'failed');
    }        

    protected function setUpTerminalOnboardingFailureCases()
    {
        $subMerchant = $this->fixtures->create('merchant');

        $subMerchantId = $subMerchant->getId();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        // Assign submerchant to partner
        $accessMapData = [
            'entity_type'     => 'application',
            'merchant_id'     => $subMerchantId,
            'entity_owner_id' => '10000000000000',
        ];

        $this->fixtures->create('merchant_access_map', $accessMapData);

        $terminal = $this->fixtures->create('terminal', [
            'merchant_id'       => $subMerchantId,
            'enabled'           => false,
            'gateway'           => 'worldline',
            'account_number'    => '10010101011',
            'ifsc_code'         => 'RZPB0000000',
            'status'            => 'created'
            ]);

        $terminalOnboardingDetail = $this->fixtures->create('terminal_onboarding_detail', [
            'terminal_id'       => $terminal->getId(),
            'status'            => 'created',
            'attempts'          => 0,
            'verify_bucket'     => 0,
        ]);

        $subMerchant->setCategory("742");

        $subMerchant->save();

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' =>  $subMerchantId,
                'submitted'   => true,
                'locked'      => true
            ]);
        
        (new BaseFixture)->createEntityInTestAndLive('merchant_detail', [
            'merchant_id' => '10000000000000',
            'submitted'   => true,
            'business_registered_state' => 'KA',
            'locked'      => true
        ]);
        
        return [$terminal, $terminalOnboardingDetail];
    }
}
