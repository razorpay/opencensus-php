<?php

namespace Functional\Merchant;

use RZP\Models\Feature\Core;
use RZP\Models\Feature\Entity;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Service;
use RZP\Models\Merchant\Detail;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Models\Merchant\AccountV2\Metric;
use Illuminate\Database\Eloquent\Factory;
use RZP\Constants\Entity as EntityConstants;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Fixtures\Entity\Merchant;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;


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

        $response = $this->startTest();

        $accountId = $response['id'];

        $this->validateSubMerchantTagging($accountId, '10000000000000');

        $this->validateSupportingEntitiesCreation($accountId);
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

    public function testCreateSubmerchantWithNoDocFeature()
    {
        $this->setUpPartnerWithKycHandled();

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Core())->create($featureParams,true);

        $response = $this->startTest();

        $accountId = $response['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $merchant = $this->getDbEntity('merchant', ['id' => $accountId]);

        $this->assertNotNull($merchant);

        $feature = $this->getDbEntity('feature', ['name' => 'no_doc_onboarding', 'entity_id' => $accountId, 'entity_type' => 'merchant']);

        $this->assertNotNull($feature);
    }

    public function testCreateSubmerchantWithNoDocFeatureDisabled()
    {
        $this->setUpPartnerWithKycHandled();

        $this->startTest();
    }

    public function testEditSubmerchantAccountNoDocFeature()
    {
        $this->setUpPartnerWithKycHandled();

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $accountId = $result['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $feature = $this->getDbEntity('feature', ['name' => 'no_doc_onboarding', 'entity_id' => $accountId, 'entity_type' => 'merchant']);

        $this->assertNull($feature);

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Core())->create($featureParams,true);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);

        $feature = $this->getDbEntity('feature', ['name' => 'no_doc_onboarding', 'entity_id' => $accountId, 'entity_type' => 'merchant']);

        $this->assertNotNull($feature);
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

        $testData['request']['url'] = '/v2/accounts/' . $accountId;

        $this->startTest($testData);
    }

    private function getDimensionsForAccountV2Metrics()
    {
        return [
            'partner_type'              => 'aggregator',
            'submerchant_business_type' => 'individual'
        ];
    }

    private function validateSupportingEntitiesCreation(string $accountId)
    {
        Account\Entity::verifyIdAndSilentlyStripSign($accountId);
        $balance = $this->getDbEntity('balance', ['merchant_id' => $accountId]);
        $this->assertNotNull($balance);
        $balanceConfig = $this->getDbEntity('balance_config', ['balance_id' => $balance->getId()]);
        $this->assertNotNull($balanceConfig);
        $bankAccount = $this->getDbEntity('bank_account', ['merchant_id' => $accountId]);
        $this->assertNotNull($bankAccount);
        $paymentLinkFeature = $this->getDbEntity('feature', ['entity_id' => $accountId, 'name' => 'paymentlinks_v2']);
        $this->assertNotNull($paymentLinkFeature);
    }

    private function validateSubMerchantTagging(string $merchantId, string $partnerId)
    {
        $tagName = 'Ref-' . $partnerId;
        Account\Entity::verifyIdAndSilentlyStripSign($merchantId);
        $tags = (new Service())->getTags($merchantId);
        $this->assertTrue(in_array($tagName, $tags));
        $features = $this->getDbEntities('feature', ['entity_id' => $merchantId, 'name' => 'create_source_v2'], 'live');
        $this->assertTrue(count($features) === 1);
    }

    public function testGetValidationFieldsForNoDocOnboarding()
    {
        $this->setUpPartnerWithKycHandled();

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Core())->create($featureParams,true);

        $testData = $this->testData['testCreateSubmerchantWithNoDocFeature'];

        $response =  $this->runRequestResponseFlow($testData);

        $accountId = $response['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $merchant = $this->getDbEntity('merchant', ['id' => $accountId]);

        $data = (new Detail\Core())->getValidationFields($merchant->merchantDetail);

        $expectedRequiredFields = Detail\ValidationFields::DEFAULT_REGISTERED_NO_DOC_FIELDS;

        $this->assertNotNull($data);
        $this->assertEquals($expectedRequiredFields,$data[0]);
        $this->assertEquals([],$data[1]);
        $this->assertEquals([],$data[2]);

        $testData = $this->testData['testGetValidationFieldsForNoDocOnboarding'];

        $response =  $this->runRequestResponseFlow($testData);

        $accountId = $response['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $merchant = $this->getDbEntity('merchant', ['id' => $accountId]);

        $data = (new Detail\Core())->getValidationFields($merchant->merchantDetail);

        $expectedRequiredFields = Detail\ValidationFields::UNREGISTERED_NO_DOC_FIELDS;

        $this->assertNotNull($data);
        $this->assertEquals($expectedRequiredFields,$data[0]);
        $this->assertEquals([],$data[1]);
        $this->assertEquals([],$data[2]);
    }
}
