<?php

namespace RZP\Tests\Functional\Merchant;

use Carbon\Carbon;
use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\Redis;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Gateway\Terminal\GatewayProcessor\Atos;
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

    public function testTerminalOnboardingVerificationCron()
    {
        $this->ba->cronAuth();

        $terminal = $this->fixtures->create('terminal', [
            'enabled'     => false,
            'gateway'     => 'atos',
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

        $this->assertEquals($updatedTerminalOnboardingDetail['status'], 'activated');
        $this->assertNull($updatedTerminalOnboardingDetail['verify_at']);
    }

    public function testTerminalOnboardingCreationCron()
    {
        // To setup merchant_access_map etc
        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->ba->cronAuth();

        $terminal = $this->fixtures->create('terminal', [
            'merchant_id'       => $subMerchantId,
            'enabled'           => false,
            'gateway'           => 'atos',
            'account_number'    => '10010101011',
            'ifsc_code'         => 'RZPB0000000',
            'status'            => 'created'
            ]);

        $this->fixtures->create('terminal_onboarding_detail', [
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
                'locked'      => true
            ]);

        $url = '/terminals/onboard/creation';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $onboardedTerminals = $this->startTest();

        $this->assertEquals(count($onboardedTerminals['terminal_ids_fetched']), 1);

        $this->assertEquals(count($onboardedTerminals['terminal_ids_queued']), 1);

        $this->startTest();
    }
}
