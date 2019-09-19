<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\Redis;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Gateway\Terminal\GatewayProcessor\Atos;
use RZP\Tests\Functional\Fixtures\Entity\Terminal as TerminalFixture;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Models\Feature\Constants as FeatureConstants;

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

        $url = '/terminals/'.$terminal['id'] . '/enable';

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

        $url = '/terminals/' . $terminal['id'] . '/disable';

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

        $url = '/terminals/' . $terminal['id'] . '/enable';

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

        $url = '/terminals/' . $terminal['id'] . '/disable';

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
        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->redis = Redis::connection()->client();

        $ranges = [
            [123800, 123899],
            [133800, 133899],
            [143800, 143899]
        ];

        foreach ($ranges as $range)
        {
            $this->redis->rpush('test_mode_' . Atos\TidGenerator::ATOS_TID_RANGE_LIST, json_encode($range));
        }

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $url = '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }
}
