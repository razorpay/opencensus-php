<?php

namespace RZP\Tests\Functional\Merchant;

use Config;
use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Models\Partner\Config\CommissionModel;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class CommissionTest extends OAuthTestCase
{
    use CommissionTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CommissionTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->createCommissionIndex();
    }

    public function testGetCommissionsEmpty()
    {
        list($partner, $app) = $this->createPartnerAndApplication();

        $this->createConfigForPartnerApp($app->getId());
        $this->createSubMerchant($partner, $app);

        $this->ba->proxyAuth('rzp_test_' . $partner->getId());

        $this->startTest();
    }

    public function testGetCommissions()
    {
        list($partner, $subMerchant, $payment) = $this->createSampleCommission();

        $this->ba->proxyAuth('rzp_test_' . $partner->getId());

        $result = $this->startTest();

        $this->assertCommissionData($partner, $subMerchant, $payment, $result['items']);
    }

    public function testGetCommissionById()
    {
        list($partner, $subMerchant, $payment, $config, $commission) = $this->createSampleCommission();

        $this->ba->proxyAuth('rzp_test_' . $partner->getId());

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/commissions/comm_' . $commission->getId();

        $this->startTest($testData);
    }

    /**
     * commissions across multiple partners should not be accessible by other partners
     */
    public function testGettingCommissionsByFilters()
    {
        list($partner, $subMerchant, $payment) = $this->createSampleCommission();

        $this->createSampleCommission(
            ['id' => 'SampleMerchant'],
            ['id' => 'SampleAppIdOne'],
            ['id' => 'SubmerchantOne']);

        $this->ba->proxyAuth('rzp_test_' . $partner->getId());

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = Payment\Entity::getSignedId($payment->getId());

        $result = $this->startTest($testData);

        // check if filtered results match the expected data
        $this->assertCommissionData($partner, $subMerchant, $payment, $result['items']);

        $testData['request']['content']['merchant_id'] = $subMerchant->getId();

        unset($testData['request']['content']['source_id']);

        $result = $this->startTest($testData);

        $this->assertCommissionData($partner, $subMerchant, $payment, $result['items']);

        // test combined filter
        $testData['request']['content']['source_id'] = Payment\Entity::getSignedId($payment->getId());

        $result = $this->startTest($testData);

        $this->assertCommissionData($partner, $subMerchant, $payment, $result['items']);

        unset($testData['request']['content']['source_id']);

        $testData['request']['content']['model'] = CommissionModel::COMMISSION;

        $this->startTest($testData);

        $this->assertCommissionData($partner, $subMerchant, $payment, $result['items']);
    }

    /**
     * commissions across multiple partners should be accessible by the admin
     */
    public function testGettingCommissionsForAdminByFilters()
    {
        list($partner, $subMerchant, $payment, $config) = $this->createSampleCommission();

        list($partner2, $subMerchant2, $payment2) = $this->createSampleCommission(
            ['id'         => 'SampleMerchant'],
            ['id'         => 'SampleAppIdOne'],
            ['id'         => 'SubmerchantOne'],
            ['created_at' => Carbon::today(Timezone::IST)->subDays(10)->getTimestamp()]);

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['partner_id'] = $partner->getId();

        $result = $this->startTest($testData);

        $expected = [
            [
                'source_id'  => $payment->getId(),
                'partner_id' => $partner->getId(),
            ],
        ];

        // check if filtered results match the expected data
        $this->assertArraySelectiveEqualsWithCount($expected, $result['items']);

        $testData['request']['content']['partner_config_id'] = $config->getId();
        unset($testData['request']['content']['partner_id']);

        $result = $this->startTest($testData);

        $this->assertArraySelectiveEqualsWithCount($expected, $result['items']);

        $expected = [
            [
                'source_id'  => $payment->getId(),
                'partner_id' => $partner->getId(),
            ],
            [
                'source_id'  => $payment2->getId(),
                'partner_id' => $partner2->getId(),
            ],
        ];

        $testData['request']['content']['source_type'] = 'payment';
        unset($testData['request']['content']['partner_config_id']);

        $result = $this->startTest($testData);

        $this->assertArraySelectiveEqualsWithCount($expected, $result['items']);

        $testData['request']['content']['status'] = 'created';
        unset($testData['request']['content']['source_type']);

        $result = $this->startTest($testData);

        $this->assertArraySelectiveEqualsWithCount($expected, $result['items']);
    }

    public function testGettingCommissionsForResellers()
    {
        list($partner) = $this->createSampleCommission(['partner_type' => 'reseller']);

        $this->ba->proxyAuth('rzp_test_' . $partner->getId());

        $this->startTest();
    }

    public function testGettingAnalyticsForResellerHavingLessSubMerchants()
    {
        list($partner) = $this->createSampleCommission(['partner_type' => 'reseller']);

        $this->ba->proxyAuth('rzp_test_'. $partner->getId());

        $this->startTest();
    }

    public function testGettingAnalyticsForResellerHavingMoreSubMerchants()
    {
        list($partner, $subMerchant, $payment, $config) = $this->createSampleCommission(['partner_type' => 'reseller']);

        $application = $config->entity;

        $this->createSubMerchant($partner, $application, ['id' => 'submerchant001']);
        $this->createSubMerchant($partner, $application, ['id' => 'submerchant002']);

        $this->fixtures->merchant_detail->edit($subMerchant->getId(), ['activation_status' => 'activated']);
        $this->fixtures->merchant_detail->edit('submerchant001', ['activation_status' => 'activated']);
        $this->fixtures->merchant_detail->edit('submerchant002', ['activation_status' => 'activated']);

        $this->ba->proxyAuth('rzp_test_'. $partner->getId());

        $result = $this->startTest();

        $this->assertArrayHasKey('recent_payments', $result);
    }

    public function testGettingAnalyticsForAggregator()
    {
        list($partner) = $this->createSampleCommission();

        $this->ba->proxyAuth('rzp_test_'. $partner->getId());

        $result = $this->startTest();

        $this->assertArrayHasKey('recent_payments', $result);
    }

    protected function assertCommissionData($partner, $subMerchant, $source, $result)
    {
        $expected = [
            [
                'source'      => [
                    'id' => $source->getPublicId(),
                ],
                'merchant'    => [
                    'id' => $subMerchant->getId(),
                ],
                'source_id'   => $source->getId(),
                'source_type' => $source->getEntity(),
                'partner_id'  => $partner->getId(),
                'model'       => 'commission',
            ]
        ];

        $this->assertArraySelectiveEqualsWithCount($expected, $result);
    }

    protected function assertArraySelectiveEqualsWithCount(array $expected, array $actual)
    {
        $this->assertArraySelectiveEquals($expected, $actual);
        $this->assertCount(count($expected), $actual);
    }
}
