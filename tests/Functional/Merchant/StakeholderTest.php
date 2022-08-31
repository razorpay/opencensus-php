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

        $key = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $key = 'rzp_live_' . $key->getKey();

        $this->ba->privateAuth($key);

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
