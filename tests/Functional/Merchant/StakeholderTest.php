<?php

namespace Functional\Merchant;

use RZP\Constants\Mode;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Models\Merchant\Stakeholder\Metric;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Constants as MerchantConstants;

class StakeholderTest extends OAuthTestCase
{
    use TestsMetrics;
    use PartnerTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/StakeholderTestData.php';
        parent::setUp();
    }

    public function testCreateStakeholderForCompletelyFilledRequest()
    {
        list($partner, $app) = $this->createPartnerAndApplication();
        $this->fixtures->merchant->activate($partner->getId());

        $this->createConfigForPartnerApp($app->getId());
        list($subMerchant) = $this->createSubMerchant($partner, $app);

        $liveKey = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $liveKey = 'rzp_live_' . $liveKey->getKey();

        $testKey = $this->fixtures->on(Mode::TEST)->create('key', ['merchant_id' => $partner->getId()]);
        $testKey = 'rzp_live_' . $testKey->getKey();

        $this->ba->privateAuth($liveKey);

        $metricsMock = $this->createMetricsMock();

        $metricCaptured = false;

        $expectedMetricData = $this->getStakeholderMetricData($partner);

        $this->mockAndCaptureCountMetric(Metric::STAKEHOLDER_V2_CREATE_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);
        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/stakeholders';
        $response = $this->runRequestResponseFlow($testData);
        $this->assertTrue($metricCaptured);

        $metricCaptured = false;
        $this->mockAndCaptureCountMetric(Metric::STAKEHOLDER_V2_FETCH_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);
        $testData = $this->testData['testFetchStakeholder'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/stakeholders/'. $response['id'];
        $this->runRequestResponseFlow($testData);
        $this->assertTrue($metricCaptured);

        // check that the address has synced to test mode as well
        $this->ba->privateAuth($testKey);
        $testData = $this->testData['testFetchStakeholder'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/stakeholders/'. $response['id'];
        $this->runRequestResponseFlow($testData);

        $this->ba->privateAuth($liveKey);
        $metricCaptured = false;
        $this->mockAndCaptureCountMetric(Metric::STAKEHOLDER_V2_UPDATE_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);
        $testData = $this->testData['testUpdateStakeholderCompleteRequest'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/stakeholders/'. $response['id'];
        $this->runRequestResponseFlow($testData);
        $this->assertTrue($metricCaptured);

        $testData = $this->testData['testFetchAllAccountStakeholders'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/stakeholders';
        $this->runRequestResponseFlow($testData);
    }

    public function testCreateStakeholderByPlatformPartner()
    {
        $this->setPurePlatformContext(Mode::TEST, false);

        $this->fixtures->merchant->addFeatures(['cobranded_onboarding'], Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $subMerchantDetails = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            'business_type' => 2,
            'business_category' => 'financial_services',
            'business_subcategory' => 'mutual_fund',
        ];

        $this->fixtures->merchant_detail->createMerchantDetail($subMerchantDetails);

        $metricsMock = $this->createMetricsMock();

        $metricCaptured = false;

        $expectedMetricData = [
            'partner_type'   => MerchantConstants::PURE_PLATFORM
        ];

        $this->mockAndCaptureCountMetric(Metric::STAKEHOLDER_V2_CREATE_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);
        $testData = $this->testData['testCreateStakeholderForCompletelyFilledRequest'];
        $testData['request']['url'] = '/v2/accounts/acc_'. Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID .'/stakeholders';
        $response = $this->runRequestResponseFlow($testData);
        $this->assertTrue($metricCaptured);

        $metricCaptured = false;
        $this->mockAndCaptureCountMetric(Metric::STAKEHOLDER_V2_FETCH_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);
        $testData = $this->testData['testFetchStakeholder'];
        $testData['request']['url'] = '/v2/accounts/acc_'. Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID .'/stakeholders/'. $response['id'];
        $this->runRequestResponseFlow($testData);
        $this->assertTrue($metricCaptured);

        $metricCaptured = false;
        $this->mockAndCaptureCountMetric(Metric::STAKEHOLDER_V2_UPDATE_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);
        $testData = $this->testData['testUpdateStakeholderCompleteRequest'];
        $testData['request']['url'] = '/v2/accounts/acc_'. Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID .'/stakeholders/'. $response['id'];
        $this->runRequestResponseFlow($testData);
        $this->assertTrue($metricCaptured);

        $testData = $this->testData['testFetchAllAccountStakeholders'];
        $testData['request']['url'] = '/v2/accounts/acc_'. Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID .'/stakeholders';
        $this->runRequestResponseFlow($testData);
    }

    /**
     * Tests that the stakeholder's address, when created using test key,
     * syncs to live and test DB
     *
     * @return void
     */
    public function testStakeholderAddressSyncingToLiveAndTest()
    {
        list($partner, $app) = $this->createPartnerAndApplication();
        $this->fixtures->merchant->activate($partner->getId());

        $this->createConfigForPartnerApp($app->getId());
        list($subMerchant) = $this->createSubMerchant($partner, $app);

        $liveKey = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $liveKey = 'rzp_live_' . $liveKey->getKey();

        $testKey = $this->fixtures->on(Mode::TEST)->create('key', ['merchant_id' => $partner->getId()]);
        $testKey = 'rzp_live_' . $testKey->getKey();

        $this->ba->privateAuth($testKey);

        // create stakeholder with test key
        $testData = $this->testData['testCreateStakeholderForCompletelyFilledRequest'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/stakeholders';
        $response = $this->runRequestResponseFlow($testData);

        // fetch with test key
        $testData = $this->testData['testFetchStakeholder'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/stakeholders/'. $response['id'];
        $this->runRequestResponseFlow($testData);

        // fetch with live key
        $this->ba->privateAuth($liveKey);
        $testData = $this->testData['testFetchStakeholder'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/stakeholders/'. $response['id'];
        $this->runRequestResponseFlow($testData);

    }

    public function testProvideStakeholderOptionalFieldsForNoDocMerchantInNCState()
    {
        list($subMerchant, $partner) = $this->setupPrivateAuthForPartner();

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id' => $subMerchant->getId()
        ]);

        $attribute = [
            'activation_status' => 'needs_clarification',
            'business_type' => '3'
        ];

        $this->fixtures->on('test')->edit('merchant_detail', $subMerchant->getId(), $attribute);

        $this->fixtures->on('live')->edit('merchant_detail', $subMerchant->getId(), $attribute);

        $this->fixtures->create('feature', [
            'name'        => 'no_doc_onboarding',
            'entity_id'   => $subMerchant->getId(),
            'entity_type' => 'merchant'
        ]);

        $testData = $this->testData['testUpdateStakeholderThinToCompleteRequest'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() . '/stakeholders/sth_' . $stakeholder->getId();

        $this->runRequestResponseFlow($testData);
    }

    public function testCreateStakeholderInvalidPercentageOwnership()
    {
        list($partner, $app) = $this->createPartnerAndApplication();
        $this->fixtures->merchant->activate($partner->getId());

        $this->createConfigForPartnerApp($app->getId());
        list($subMerchant) = $this->createSubMerchant($partner, $app);

        $key = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $key = 'rzp_live_' . $key->getKey();

        $this->ba->privateAuth($key);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/stakeholders';
        $this->runRequestResponseFlow($testData);
    }

    public function testCreateStakeholderForThinRequest()
    {
        list($partner, $app) = $this->createPartnerAndApplication();
        $this->fixtures->merchant->activate($partner->getId());

        $this->createConfigForPartnerApp($app->getId());
        list($subMerchant) = $this->createSubMerchant($partner, $app);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/stakeholders';

        $key = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $key = 'rzp_live_' . $key->getKey();

        $this->ba->privateAuth($key);

        $response = $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testUpdateStakeholderThinToCompleteRequest'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/stakeholders/'. $response['id'];
        $this->runRequestResponseFlow($testData);
    }

    public function testCreateStakeholderWithAccessDenied()
    {
        list($partner, $app) = $this->createPartnerAndApplication();
        $this->fixtures->merchant->activate($partner->getId());

        $this->createConfigForPartnerApp($app->getId());
        list($subMerchant) = $this->createSubMerchant($partner, $app);

        $liveKey = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $liveKey = 'rzp_live_' . $liveKey->getKey();

        $this->ba->privateAuth($liveKey);
        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/stakeholders';

        $this->blockOnboardingApisAccess($partner->id);
        $response = $this->runRequestResponseFlow($testData);
    }

    public function testUpdateStakeholderWithAccessDenied()
    {
        $this->setPurePlatformContext(Mode::TEST, false);

        $this->fixtures->merchant->addFeatures(['cobranded_onboarding'], Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $subMerchantDetails = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            'business_type' => 2,
            'business_category' => 'financial_services',
            'business_subcategory' => 'mutual_fund',
        ];

        $this->fixtures->merchant_detail->createMerchantDetail($subMerchantDetails);

        $testData = $this->testData['testCreateStakeholderForCompletelyFilledRequest'];
        $testData['request']['url'] = '/v2/accounts/acc_'. Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID .'/stakeholders';
        $response = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/v2/accounts/acc_'. Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID .'/stakeholders/'. $response['id'];
        $this->blockOnboardingApisAccess('1000000000plat');
        $this->runRequestResponseFlow($testData);
    }

    private function getStakeholderMetricData($partner): array
    {
        return [
            'partner_type'   => $partner->getPartnerType()
        ];
    }

    protected function setupPrivateAuthForPartner()
    {
        list($partner, $app) = $this->createPartnerAndApplication();
        $this->fixtures->merchant->activate($partner->getId());

        $this->createConfigForPartnerApp($app->getId());
        list($subMerchant) = $this->createSubMerchant($partner, $app);

        $key = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $key = 'rzp_live_' . $key->getKey();

        $this->ba->privateAuth($key);

        return [$subMerchant, $partner];
    }
}
