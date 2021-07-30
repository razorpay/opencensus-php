<?php

namespace Functional\Merchant;

use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Models\Merchant\AccountV2\Metric;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class AccountV2Test extends TestCase
{
    use TestsMetrics;
    use PartnerTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    const RZP_ORG = '100000razorpay';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/AccountV2TestData.php';

        parent::setUp();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);
    }

    public function testCreateAccountV2ForMandatoryFilledRequest()
    {
        $this->setUpPartnerWithKycHandled();

        $this->startTest();

    }

    public function testCreateAccountV2ForCompletelyFilledRequest()
    {
        $this->setUpPartnerWithKycHandled();

        $metricsMock = $this->createMetricsMock();

        $expectedMetricData = $this->getDimensionsForAccountV2Metrics();

        $metricCaptured = false;

        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_CREATE_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $response = $this->startTest();

        // check that stakeholder is not yet created
        $accountId = $response['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);
        $stakeholders = $this->getDbEntities('stakeholder', ['merchant_id' => $accountId])->toArray();

        $this->assertEmpty($stakeholders);

        $this->assertTrue($metricCaptured);
    }

    public function testCreateAccountV2ForCompletelyFilledRegisteredBusinessRequest()
    {
        $this->setUpPartnerWithKycHandled();

        $response = $this->startTest();

        // check that stakeholder is not yet created
        $accountId = $response['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);
        $stakeholders = $this->getDbEntities('stakeholder', ['merchant_id' => $accountId])->toArray();

        $this->assertEmpty($stakeholders);

        $merchant = $this->getDbEntity('merchant', ['id' => $accountId]);

        $this->assertNotNull($merchant);
    }

    public function testEditAccountV2ProfileAddress()
    {
        $this->setUpPartnerWithKycHandled();

        $metricsMock = $this->createMetricsMock();

        $expectedMetricData = $this->getDimensionsForAccountV2Metrics();

        $metricCaptured = false;

        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_EDIT_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);


        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);

        $this->assertTrue($metricCaptured);
    }

    public function testEditAccountV2OtherDetails()
    {
        $this->setUpPartnerWithKycHandled();

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);
    }

    public function testFetchAccountV2()
    {
        $this->setUpPartnerWithKycHandled();

        $metricsMock = $this->createMetricsMock();

        $expectedMetricData = $this->getDimensionsForAccountV2Metrics();

        $metricCaptured = false;

        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_FETCH_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);

        $this->assertTrue($metricCaptured);
    }

    public function testDeleteAccountV2()
    {
        $this->setUpPartnerWithKycHandled();

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);
    }

    public function testEditAccountV2PostDelete()
    {
        $this->setUpPartnerWithKycHandled();

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);
        $accountId = $result['id'];

        $testData = $this->testData['testDeleteAccountV2'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId;

        $result = $this->runRequestResponseFlow($testData);

        // edit after account delete is not allowed.

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' .$accountId;

        $this->startTest($testData);
    }

    private function getDimensionsForAccountV2Metrics()
    {
        return [
            'partner_type'              => 'aggregator',
            'submerchant_business_type' => 'individual'
        ];
    }
}
