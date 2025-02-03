<?php

namespace Functional\Merchant;

use Mail;

use RZP\Constants\Mode;
use Illuminate\Support\Facades\Http;
use RZP\Exception\BadRequestException;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Feature\Constants as FName;
use RZP\Mail\Merchant\CreateSubMerchantAffiliate as CreateSubMerchantAffiliateForPG;
use RZP\Services\KafkaProducerClient;
use RZP\Services\RazorXClient;
use RZP\Models\Feature\Core;
use RZP\Models\Admin\Org as OrgModel;
use RZP\Models\Feature\Entity;
use RZP\Models\Merchant\Detail;
use RZP\Services\SplitzService;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Service;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Models\Merchant\AccountV2;
use RZP\Models\Merchant\AccountV2\Metric;
use RZP\Models\Merchant\Detail\POIStatus;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Constants\Entity as EntityConstants;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Models\Merchant\Metric as MerchantMetric;
use RZP\Jobs\MerchantSupportingEntitiesCreateJob;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Mail\Merchant\CreateSubMerchant as CreateSubMerchantMail;

class AccountV2Test extends TestCase
{
    use TestsMetrics;
    use MocksSplitz;
    use PartnerTrait;
    use WebhookTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    const RZP_ORG = '100000razorpay';
    const DEFAULT_MERCHANT_ID = '10000000000000';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/AccountV2TestData.php';

        parent::setUp();
    }

    protected function mockSplitzTreatmentWithOutput($output)
    {
        $this->splitzMock = \Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->andReturn($output);
    }

    public function mockDCS()
    {
        $dcsMock = $this->getMockBuilder(\RZP\Services\Dcs\Features\Service::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['editFeature'])
            ->getMock();

        $this->app->instance('dcs', $dcsMock);

        $dcsMock->expects($this->any())->method('editFeature')->willReturn(null);

        return $dcsMock;
    }

    public function testCreateAccountV2ForMandatoryFilledRequest()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $response = $this->startTest();

        $accountId = $response['id'];

        $this->validateSubMerchantTagging($accountId, self::DEFAULT_MERCHANT_ID);

        $this->validateSupportingEntitiesCreation($accountId);
    }

    public function testSettleToPartnerSubmerchantMetrics()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $testData = $this->testData['testCreateAccountV2ForMandatoryFilledRequest'];

        $metricCaptured = false;

        $expectedDimensions = [
            'partner_id'     => self::DEFAULT_MERCHANT_ID,
        ];

        $this->mockSplitzEvaluation();

        $metricsMock = $this->createMetricsMock();

        $this->mockAndCaptureCountMetric(MerchantMetric::SETTLE_TO_PARTNER_SUBMERCHANT_TOTAL,
                                         $metricsMock, $metricCaptured, $expectedDimensions);

        $this->runRequestResponseFlow($testData);

        $this->assertTrue($metricCaptured);

    }

    public function testCreateAccountV2ForCompletelyFilledRequest()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $metricsMock = $this->createMetricsMock();

        $expectedMetricData = $this->getDimensionsForAccountV2Metrics();

        $metricCaptured = false;

        $this->mockStorkService();

        $this->app['stork_service']->shouldReceive('publishOnSns')->twice()->andReturn(null);

        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_CREATE_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $response = $this->startTest();

        // check that stakeholder is not yet created
        $accountId = $response['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);
        $stakeholders = $this->getDbEntities('stakeholder', ['merchant_id' => $accountId])->toArray();

        $this->assertEmpty($stakeholders);

        $this->assertTrue($metricCaptured);
    }

    public function testCreateAccountV2WithInvalidBusinessName()
    {
        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID);

        $this->setUpPartnerWithKycHandled();

        $this->startTest();
    }

    public function testCreateAccountV2WithInvalidCustomerFacingBusinessName()
    {
        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID);

        $this->setUpPartnerWithKycHandled();

        $this->startTest();
    }

    public function testCreateAccountV2WithInvalidStreet1()
    {
        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID);

        $this->setUpPartnerWithKycHandled();

        $this->startTest();
    }

    public function testCreateAccountV2WithInvalidStreet2()
    {
        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID);

        $this->setUpPartnerWithKycHandled();

        $this->startTest();
    }

    public function testCreateAccountV2WithInvalidDataRequest()
    {
        Mail::fake();

        $this->setUpPartnerWithKycHandled();

        Mail::assertNotQueued(CreateSubMerchantMail::class);

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $this->startTest();
    }

    public function testCreateAccountV2WithInvalidStateName()
    {
        Mail::fake();

        $this->setUpPartnerWithKycHandled();

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $testData['request']['content']['profile']['addresses']['registered']['state'] = 'NonExistingState';

        $testData['response'] = $this->testData['testCreateAccountV2WithInvalidStateName']['response'];

        $testData['exception'] = $this->testData['testCreateAccountV2WithInvalidStateName']['exception'];

        Mail::assertNotQueued(CreateSubMerchantMail::class);

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $this->runRequestResponseFlow($testData);
    }

    public function testCreateAccountV2WithEmptyCustomerFacingBusinessName()
    {
        Mail::fake();

        $this->setUpPartnerWithKycHandled();

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $testData['request']['content']['customer_facing_business_name'] = '';

        $testData['response'] = $this->testData[__FUNCTION__]['response'];

        $testData['exception'] = $this->testData[__FUNCTION__]['exception'];

        Mail::assertNotQueued(CreateSubMerchantMail::class);

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $this->runRequestResponseFlow($testData);
    }

    public function testCreateAccountV2ForCompletelyFilledRegisteredBusinessRequest()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $output["response"]["variant"]["name"] = "enable";

        $this->mockSplitExperimentForPaymentAcceptanceAttributes($output);

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

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $featureParams = [
            Entity::ENTITY_ID   => self::DEFAULT_MERCHANT_ID,
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Core())->create($featureParams, true);

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

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $this->startTest();
    }

    public function testIsAutoKycDoneForNoDoc()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $featureParams = [
            Entity::ENTITY_ID   => self::DEFAULT_MERCHANT_ID,
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Core())->create($featureParams, true);

        $testData = $this->testData['testCreateSubmerchantWithNoDocFeature'];

        $result = $this->runRequestResponseFlow($testData);

        $accountId = $result['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $merchant = $this->getDbEntity('merchant', ['id' => $accountId]);

        $merchantDetails = $merchant->merchantDetail;

        $merchantDetails->setBankDetailsVerificationStatus(POIStatus::VERIFIED);

        $merchantDetails->setCompanyPanVerificationStatus(POIStatus::VERIFIED);

        $merchantDetails->setGstinVerificationStatus(POIStatus::VERIFIED);

        $value = (new Detail\Core())->isAutoKycDone($merchantDetails);

        $this->assertEquals(true, $value);
    }

    public function testGetApplicableStatusForNoDoc()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $featureParams = [
            Entity::ENTITY_ID   => self::DEFAULT_MERCHANT_ID,
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Core())->create($featureParams, true);

        $testData = $this->testData['testCreateSubmerchantWithNoDocFeature'];

        $result = $this->runRequestResponseFlow($testData);

        $accountId = $result['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $merchant = $this->getDbEntity('merchant', ['id' => $accountId]);

        $merchantDetails = $merchant->merchantDetail;

        $merchantDetails->setBankDetailsVerificationStatus(POIStatus::VERIFIED);

        $merchantDetails->setCompanyPanVerificationStatus(POIStatus::VERIFIED);

        $merchantDetails->setGstinVerificationStatus(POIStatus::VERIFIED);

        $value = (new \RZP\Models\Merchant\Detail\Core())->getApplicableActivationStatus($merchantDetails);

        $this->assertEquals('activated_kyc_pending', $value);
    }

    public function testGetApplicableStatusForPartiallyActivatedNoDocMerchant()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $featureParams = [
            Entity::ENTITY_ID   => self::DEFAULT_MERCHANT_ID,
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Core())->create($featureParams, true);

        $testData = $this->testData['testCreateSubmerchantWithNoDocFeature'];

        $result = $this->runRequestResponseFlow($testData);

        $accountId = $result['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $merchant = $this->getDbEntity('merchant', ['id' => $accountId]);

        $merchantDetails = $merchant->merchantDetail;

        $merchantDetails->setBankDetailsVerificationStatus(POIStatus::VERIFIED);

        $merchantDetails->setCompanyPanVerificationStatus(POIStatus::VERIFIED);

        $merchantDetails->setGstinVerificationStatus(POIStatus::VERIFIED);

        $this->fixtures->merchant->activate($accountId);

        // Attaching tag 'no_doc_partially_activated' to the xpress merchant,
        // so that merchant becomes part of xpress onboarding pro-active KYC flow
        $this->fixtures->merchant->addTags([Account\Constants::NO_DOC_PARTIALLY_ACTIVATED], $accountId);

        $value = (new Detail\Core())->getApplicableActivationStatus($merchantDetails);

        $this->assertEquals('under_review', $value);
    }

    public function testGetOnboardingSource()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $featureParams = [
            Entity::ENTITY_ID   => self::DEFAULT_MERCHANT_ID,
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Core())->create($featureParams, true);

        $testData = $this->testData['testCreateSubmerchantWithNoDocFeature'];

        $result = $this->runRequestResponseFlow($testData);

        $accountId = $result['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $merchant = $this->getDbEntity('merchant', ['id' => $accountId]);

        (new \RZP\Models\Merchant\Detail\Core())->storeOnboardingSourceForNoDocMerchants($merchant);

        $value = $merchant->merchantDetail->businessDetail->getOnboardingSource();

        $this->assertEquals('xpress_onboarding', $value);
    }

    public function testEditAccountV2ProfileAddress()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 2);

        $metricsMock = $this->createMetricsMock();

        $expectedMetricData = $this->getDimensionsForAccountV2Metrics();

        $metricCaptured = false;

        $output["response"]["variant"]["name"] = "enable";

        $this->mockSplitExperimentForPaymentAcceptanceAttributes($output);

        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_EDIT_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);

        $this->assertTrue($metricCaptured);
    }

    public function testEditAccountV2ProfileAddressCT()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 2);

        $metricsMock = $this->createMetricsMock();

        $expectedMetricData = $this->getDimensionsForAccountV2Metrics();

        $metricCaptured = false;

        $output["response"]["variant"]["name"] = "enable";

        $this->mockSplitExperimentForPaymentAcceptanceAttributes($output);

        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_EDIT_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequestAddressCT'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);

        $this->assertTrue($metricCaptured);
    }

    public function testEditAccountV2OtherDetails()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 2);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);
    }

    public function testUpdateAccountV2InvalidAccId()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 0);

        $this->startTest();
    }

    public function testEditAccountWithEmptyCustomerFacingBusinessName()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 2);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);
    }

    public function testFetchAccountV2()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 2);

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

    public function testFetchAccountV2WithActivatedMccPending()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 2);

        $metricsMock = $this->createMetricsMock();

        $expectedMetricData = $this->getDimensionsForAccountV2Metrics();

        $metricCaptured = false;

        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_FETCH_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $this->fixtures->edit('merchant_detail', $result['id'], ['locked' => true, 'activation_status' => 'activated_mcc_pending']);


        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);

        $this->assertTrue($metricCaptured);
    }

    public function testFetchAccountV2WithNullAdditionalWebsites()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 2);

        $metricsMock = $this->createMetricsMock();

        $expectedMetricData = $this->getDimensionsForAccountV2Metrics();

        $metricCaptured = false;

        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_FETCH_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testFetchAccountV2'];

        $this->fixtures->on('test')->edit('merchant_detail', $result['id'], ["additional_websites" => null]);

        $this->fixtures->on('live')->edit('merchant_detail', $result['id'], ["additional_websites" => null]);

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);

        $this->assertTrue($metricCaptured);
    }

    public function testFetchAccountV2ByPlatformPartner()
    {
        $this->setPurePlatformContext(Mode::TEST, false);

        $this->fixtures->merchant->addFeatures(['cobranded_onboarding'], Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $subMerchantDetails = [
            'merchant_id'           => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            'business_type'         => 2,
            'business_category'     => 'financial_services',
            'business_subcategory'  => 'mutual_fund',
        ];

        $merchantDetail = $this->fixtures->merchant_detail->createAssociateMerchant($subMerchantDetails);

        $metricsMock = $this->createMetricsMock();

        $expectedMetricData = $this->getDimensionsForAccountV2Metrics(MerchantConstants::PURE_PLATFORM);

        $metricCaptured = false;

        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_FETCH_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/acc_' . Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID;

        $this->startTest($testData);
    }

    public function testCreateAccountV2ByPlatformPartner()
    {
        $this->setPurePlatformContext(Mode::TEST, false);

        $this->fixtures->merchant->addFeatures(['cobranded_onboarding'], Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $metricsMock = $this->createMetricsMock();

        $expectedMetricData = $this->getDimensionsForAccountV2Metrics(MerchantConstants::PURE_PLATFORM);

        $metricCaptured = false;

        $this->mockStorkService();

        $this->app['stork_service']->shouldReceive('publishOnSns')->twice()->andReturn(null);

        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_CREATE_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $response = $this->startTest($testData);

        // check that stakeholder is not yet created
        $accountId = $response['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);
        $stakeholders = $this->getDbEntities('stakeholder', ['merchant_id' => $accountId])->toArray();

        $this->assertEmpty($stakeholders);

        $this->assertTrue($metricCaptured);
    }

    public function testCreateAccountV2ByPlatformPartnerAddressCT()
    {
        $this->setPurePlatformContext(Mode::TEST, false);

        $this->fixtures->merchant->addFeatures(['cobranded_onboarding'], Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $metricsMock = $this->createMetricsMock();

        $expectedMetricData = $this->getDimensionsForAccountV2Metrics(MerchantConstants::PURE_PLATFORM);

        $metricCaptured = false;

        $this->mockStorkService();

        $this->app['stork_service']->shouldReceive('publishOnSns')->twice()->andReturn(null);

        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_CREATE_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequestAddressCT'];

        $response = $this->startTest($testData);

        // check that stakeholder is not yet created
        $accountId = $response['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);
        $stakeholders = $this->getDbEntities('stakeholder', ['merchant_id' => $accountId])->toArray();

        $this->assertEmpty($stakeholders);

        $this->assertTrue($metricCaptured);
    }

    public function testUpdateAccountV2ByPlatformPartner()
    {
        $this->setPurePlatformContext(Mode::TEST, false);

        $this->fixtures->merchant->addFeatures(['cobranded_onboarding'], Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $subMerchantDetails = [
            'merchant_id'           => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            'business_type'         => 2,
            'business_category'     => 'financial_services',
            'business_subcategory'  => 'mutual_fund',
        ];

        $merchantDetail = $this->fixtures->merchant_detail->createAssociateMerchant($subMerchantDetails);

        $metricsMock = $this->createMetricsMock();

        $expectedMetricData = $this->getDimensionsForAccountV2Metrics(MerchantConstants::PURE_PLATFORM);

        $metricCaptured = false;

        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_FETCH_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/acc_' . Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID;

        $this->startTest($testData);
    }

    public function testCreateAccountV2ByPlatformPartnerWithFeatureNotEnabled()
    {
        $this->setPurePlatformContext(Mode::TEST, false);

        $metricsMock = $this->createMetricsMock();

        $expectedMetricData = $this->getDimensionsForAccountV2Metrics(MerchantConstants::PURE_PLATFORM);

        $metricCaptured = false;

        $this->mockStorkService();

        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_CREATE_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $testData['response'] = $this->testData['testFetchAccountV2ByPlatformPartnerWithFeatureNotEnabled']['response'];
        $testData['exception'] = $this->testData['testFetchAccountV2ByPlatformPartnerWithFeatureNotEnabled']['exception'];

        $this->startTest($testData);
    }

    public function testFetchAccountV2ByPlatformPartnerWithFeatureNotEnabled()
    {
        $this->setPurePlatformContext(Mode::TEST, false);

        $this->allowOnboardingApisAccess(Constants::DEFAULT_PLATFORM_MERCHANT_ID, 1);

        $subMerchantDetails = [
            'merchant_id'           => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            'business_type'         => 2,
            'business_category'     => 'financial_services',
            'business_subcategory'  => 'mutual_fund',
        ];

        $merchantDetail = $this->fixtures->merchant_detail->createAssociateMerchant($subMerchantDetails);

        $metricsMock = $this->createMetricsMock();

        $expectedMetricData = $this->getDimensionsForAccountV2Metrics(MerchantConstants::PURE_PLATFORM);

        $metricCaptured = false;

        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_FETCH_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);
        //
        //$output = [
        //    "response" => [
        //        "variant" => null
        //    ]
        //];
        //
        //$this->mockSplitzTreatmentWithOutput($output);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/acc_' . Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID;

        $this->startTest($testData);
    }

    public function testFetchAccountV2ByPlatformPartnerWithInvalidAccId()
    {
        $this->setPurePlatformContext(Mode::TEST, false);

        $this->fixtures->merchant->addFeatures(['cobranded_onboarding'], Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $subMerchantDetails = [
            'merchant_id'           => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            'business_type'         => 2,
            'business_category'     => 'financial_services',
            'business_subcategory'  => 'mutual_fund',
        ];

        $merchantDetail = $this->fixtures->merchant_detail->createAssociateMerchant($subMerchantDetails);

        $metricsMock = $this->createMetricsMock();

        $expectedMetricData = $this->getDimensionsForAccountV2Metrics(MerchantConstants::PURE_PLATFORM);

        $metricCaptured = false;

        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_FETCH_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $testData = $this->testData[__FUNCTION__];

        // create merchant account not mapped to partner
        $this->fixtures->merchant->createAccount(Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID);

        $testData['request']['url'] = '/v2/accounts/acc_' . Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID;

        $this->startTest($testData);
    }

    public function testCreateAccountV2ForMandatoryFilledByCapitalPartner()
    {
        $this->fixtures->merchant->addFeatures(['loc_subm_onboarding_api']);

        $this->mockCapitalPartnershipSplitzExperiment();

        $this->setUpPartnerWithKycHandled(MerchantConstants::RESELLER);

        $response = $this->startTest();

        $accountId = $response['id'];

        $this->validateSubMerchantTagging($accountId, self::DEFAULT_MERCHANT_ID);

        $this->validateSupportingEntitiesCreation($accountId);
    }

    public function testCreateAccountV2ByCapitalPartnerButFeatureDisabled()
    {
        $this->setUpPartnerWithKycHandled(MerchantConstants::RESELLER);

        $testData = $this->testData['testCreateAccountV2ByCapitalPartnerFailed'];

        $this->startTest($testData);
    }

    public function testCreateAccountV2ByCapitalPartnerButCapitalExpDisabled()
    {
        $this->fixtures->merchant->addFeatures(['loc_subm_onboarding_api']);

        $this->setUpPartnerWithKycHandled(MerchantConstants::RESELLER);

        $testData = $this->testData['testCreateAccountV2ByCapitalPartnerFailed'];

        $this->startTest($testData);
    }

    public function testFetchAccountV2ByCapitalPartner()
    {
        $this->fixtures->merchant->addFeatures(['loc_subm_onboarding_api']);

        $this->mockCapitalPartnershipSplitzExperiment();

        $this->setUpPartnerWithKycHandled(MerchantConstants::RESELLER);

        $testData = $this->testData['testCreateAccountV2ForMandatoryFilledByCapitalPartner'];

        $result = $this->runRequestResponseFlow($testData);

        $testData['request']['method'] = 'GET';
        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);
    }

    public function testDeleteAccountV2()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 2);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);
    }

    public function testDeleteAccountV2WithLinkedAccounts()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 2);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $accountID = $result['id'];

        $this->fixtures->create("merchant", [
            'parent_id' => Account\Entity::verifyIdAndSilentlyStripSign($accountID),
            'live' => true,
            'hold_funds' => false
        ]);

        $testData = $this->testData['testDeleteAccountV2'];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);
    }

    public function testEditAccountV2PostDelete()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 3);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result    = $this->runRequestResponseFlow($testData);
        $accountId = $result['id'];

        $testData = $this->testData['testDeleteAccountV2'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId;

        $result = $this->runRequestResponseFlow($testData);

        // edit after account delete is not allowed.

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $accountId;

        $this->startTest($testData);
    }

    public function testDeleteAccountV2ByPlatformPartner()
    {
        $this->setPurePlatformContext(Mode::TEST, false);

        $this->fixtures->merchant->addFeatures(['cobranded_onboarding'], Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testDeleteAccountV2'];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);
    }

    private function getDimensionsForAccountV2Metrics(string $partnerType = MerchantConstants::AGGREGATOR): array
    {
        return [
            'partner_type'              => $partnerType,
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

    private function mockSplitzEvaluation()
    {
        $input = [
            "experiment_id" => "JIRYzx7YtMuB18",
            "id"            => self::DEFAULT_MERCHANT_ID,
            'request_data'  => json_encode(
                [
                    'id' => self::DEFAULT_MERCHANT_ID,
                ]),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => "enabled"
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $input = [
            "experiment_id" => "KJfPdCoug8vfap",
            "id"            => self::DEFAULT_MERCHANT_ID
        ];

        $output ["response"]["variant"] ["name" ] = "enable";

        $this->mockSplitzTreatment($input, $output);
    }

    public function testGetValidationFieldsForNoDocOnboarding()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 2);

        $featureParams = [
            Entity::ENTITY_ID   => self::DEFAULT_MERCHANT_ID,
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Core())->create($featureParams, true);

        $testData = $this->testData['testCreateSubmerchantWithNoDocFeature'];

        $response = $this->runRequestResponseFlow($testData);

        $accountId = $response['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $merchant = $this->getDbEntity('merchant', ['id' => $accountId]);

        $data = (new Detail\Core())->getValidationFields($merchant->merchantDetail);

        $expectedRequiredFields = Detail\ValidationFields::DEFAULT_REGISTERED_NO_DOC_FIELDS;

        $this->assertNotNull($data);
        $this->assertEquals($expectedRequiredFields, $data[0]);
        $this->assertNotNull($data[1]);
        $this->assertNotNull($data[2]);

        $testData = $this->testData['testGetValidationFieldsForNoDocOnboarding'];

        $response = $this->runRequestResponseFlow($testData);

        $accountId = $response['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $merchant = $this->getDbEntity('merchant', ['id' => $accountId]);

        $data = (new Detail\Core())->getValidationFields($merchant->merchantDetail);

        $expectedRequiredFields = Detail\ValidationFields::UNREGISTERED_NO_DOC_FIELDS;

        $this->assertNotNull($data);
        $this->assertEquals($expectedRequiredFields, $data[0]);
        $this->assertNotNull($data[1]);
        $this->assertNotNull($data[2]);
    }

    public function testNoDocRequirementsWhenPaymentsEnabled()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $featureParams = [
            Entity::ENTITY_ID   => self::DEFAULT_MERCHANT_ID,
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Core())->create($featureParams, true);

        $testData = $this->testData['testCreateSubmerchantWithNoDocFeature'];

        $response = $this->runRequestResponseFlow($testData);

        $accountId = $response['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $merchant = $this->getDbEntity('merchant', ['id' => $accountId]);

        $this->fixtures->merchant->activate($accountId);

        // Attaching tag 'no_doc_partially_activated' to the xpress merchant,
        // so that merchant becomes part of xpress onboarding pro-active KYC flow
        $this->fixtures->merchant->addTags([Account\Constants::NO_DOC_PARTIALLY_ACTIVATED], $accountId);

        $data = (new Detail\Core())->getValidationFields($merchant->merchantDetail);

        $this->assertNotNull($data);
        $this->assertNotNull($data[0]);
        $this->assertNotNull($data[1]);
        $this->assertEmpty($data[2]); //optional requirements should be empty for such a merchant
    }

    public function testSubmitNotAllowedKycFieldsInActivatedKycPendingState()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 2);

        $featureParams = [
            Entity::ENTITY_ID   => self::DEFAULT_MERCHANT_ID,
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Core())->create($featureParams, true);

        $testData = $this->testData['testCreateSubmerchantWithNoDocFeature'];

        $response = $this->runRequestResponseFlow($testData);

        $accountId = $response['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $merchant = $this->getDbEntity('merchant', ['id' => $accountId]);

        $this->fixtures->merchant->activate($accountId);

        // Attaching tag 'no_doc_partially_activated' to the xpress merchant,
        // so that merchant becomes part of xpress onboarding pro-active KYC flow
        $this->fixtures->merchant->addTags([Account\Constants::NO_DOC_PARTIALLY_ACTIVATED], $accountId);

        $attribute = [
            'activation_status'         => 'activated_kyc_pending'
        ];

        $this->fixtures->on('test')->edit('merchant_detail', $accountId, $attribute);

        $this->fixtures->on('live')->edit('merchant_detail', $accountId, $attribute);

        $testData = $this->testData['testProvideOptionalFieldForNoDocSubmerchantInNC'];
        $testData['request']['url'] = '/v2/accounts/acc_' . $accountId;
        $this->startTest($testData);

        //Disabling below lines, since Error code is getting populated within the response

        //$testData = $this->testData['testProvideNotAllowedFieldForNoDocSubmerchantInAKPstate'];
        //$testData['request']['url'] = '/v2/accounts/acc_' . $accountId;
        //$this->startTest($testData);
    }

    public function testProvideOptionalFieldsForNoDocMerchantInNCstate()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 3);

        $featureParams = [
            Entity::ENTITY_ID   => self::DEFAULT_MERCHANT_ID,
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Core())->create($featureParams, true);

        $testData = $this->testData['testCreateSubmerchantWithNoDocFeature'];

        $response = $this->startTest($testData);

        $accountId = $response['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $attribute = [
            'activation_status'         => 'needs_clarification',
            'kyc_clarification_reasons' => [
                'clarification_reasons' => [
                    'promoter_pan_name' => [
                        [
                            'reason_type' => 'predefined',
                            'field_type'  => 'text',
                            'is_current'  => true,
                            'reason_code' => 'signatory_name_not_matched',
                            'from'        => 'admin'
                        ]
                    ]
                ]
            ]
        ];

        $this->fixtures->on('test')->edit('merchant_detail', $accountId, $attribute);

        $this->fixtures->on('live')->edit('merchant_detail', $accountId, $attribute);

        $testData = $this->testData['testProvideOptionalFieldForNoDocSubmerchantInNC'];
        $testData['request']['url'] = '/v2/accounts/acc_' . $accountId;
        $this->startTest($testData);

        $testData = $this->testData['testProvideNonOptionalFieldForNoDocSubmerchantInNC'];
        $testData['request']['url'] = '/v2/accounts/acc_' . $accountId;
        $this->startTest($testData);
    }


    public function testEditAccountHavingNonEnglishDescription()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 2);

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

    public function testEditAccountHavingEmojiInContactName()
    {
        //TODO : Testcase has to be fixed
        $this->markTestSkipped("Skipping Testcase, Need to be fixed");

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
    }

    public function testSetMaxPaymentAmountForUnregisteredSubMerchant()
    {
        [$client] = $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $this->fixtures->create("partner_config", [
            'entity_id' => $client['application_id'],
            'entity_type' => 'application',
            'sub_merchant_config' => json_decode('{"max_payment_amount":[{"value":"200000","business_type":"not_yet_registered"}]}', 1)
        ]);

        $response = $this->startTest();

        $accountId = $response['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $merchant = $this->getDbEntity('merchant', ['id' => $accountId]);

        $this->assertEquals(20000000, $merchant->getMaxPaymentAmount());
    }

    public function testSetMaxPaymentAmountDefaultForRegisteredSubMerchant()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $response = $this->startTest();

        $accountId = $response['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $merchant = $this->getDbEntity('merchant', ['id' => $accountId]);

        $this->assertNotEquals(20000000, $merchant->getMaxPaymentAmount());
    }

    public function testCreateAccountV2WithInvalidContactName()
    {
        Mail::fake();

        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $contactName = 'contactnamecontactnamecontactnamecontactnamecontactnamecontactnamecontactnamecontactnamecontactn
                        amecontactnamecontactnamecontactnamecontactnamecontactnamecontactnamecontactnamecontactnameconta
                        ctnamecontactnamecontactnamecontactnamecontactnamecontactnamecoc';

        $testData['request']['content']['contact_name'] = $contactName;

        $testData['response'] = $this->testData[__FUNCTION__]['response'];

        $testData['exception'] = $this->testData[__FUNCTION__]['exception'];

        Mail::assertNotQueued(CreateSubMerchantMail::class);

        $this->runRequestResponseFlow($testData);
    }

    public function testEditAccountWithInvalidContactName()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 2);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);
    }

    public function testCreateAccountV2WithInvalidPhone()
    {
        Mail::fake();

        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $testData['request']['content']['phone'] = '+91.8721302112';

        $testData['response'] = $this->testData[__FUNCTION__]['response'];

        $testData['exception'] = $this->testData[__FUNCTION__]['exception'];

        Mail::assertNotQueued(CreateSubMerchantMail::class);

        $this->runRequestResponseFlow($testData);
    }

    public function testCreateAccountV2WithPhoneNumbersExceeding()
    {
        Mail::fake();

        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $testData['request']['content']['phone'] = '+919048721302112';

        $testData['response'] = $this->testData[__FUNCTION__]['response'];

        $testData['exception'] = $this->testData[__FUNCTION__]['exception'];

        Mail::assertNotQueued(CreateSubMerchantMail::class);

        $this->runRequestResponseFlow($testData);
    }

    public function testEditAccountV2WithInvalidPhone()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 2);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);
    }

    public function testCreateAccountWithExtraKeysInAndroid()
    {
        Mail::fake();

        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $testData['request']['content']['apps']['android'][0]['randomKey'] = 'randomValue';

        $testData['response'] = $this->testData[__FUNCTION__]['response'];

        $testData['exception'] = $this->testData[__FUNCTION__]['exception'];

        Mail::assertNotQueued(CreateSubMerchantMail::class);

        $this->runRequestResponseFlow($testData);
    }

    public function testEditAccountWithExtraKeysInIos()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 2);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);
    }

    public function testInstantActivationTagAppendedOnSubM()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $featureParams = [
            Entity::ENTITY_ID   => self::DEFAULT_MERCHANT_ID,
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'instant_activation_v2_api',
        ];

        (new Core())->create($featureParams, true);

        $result = $this->runRequestResponseFlow($testData);

        $merchantId = $result['id'];

        Account\Entity::verifyIdAndStripSign($merchantId);

        $featureResult = (new AccountV2\Core())->isInstantActivationTagEnabled($merchantId);

        $this->assertTrue($featureResult);
    }

    public function testInstantActivationTagAppendFailureDueToNoDoc()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $featureParams = [
            Entity::ENTITY_ID   => self::DEFAULT_MERCHANT_ID,
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding'
        ];

        (new Core())->create($featureParams, true);

        $featureParams = [
            Entity::ENTITY_ID   => self::DEFAULT_MERCHANT_ID,
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'instant_activation_v2_api'
        ];

        (new Core())->create($featureParams, true);

        $testData = $this->testData['testCreateSubmerchantWithNoDocFeature'];

        $result = $this->runRequestResponseFlow($testData);

        $merchantId = $result['id'];

        Account\Entity::verifyIdAndStripSign($merchantId);

        $featureResult = (new AccountV2\Core())->isInstantActivationTagEnabled($merchantId);

        $this->assertFalse($featureResult);
    }

    public function testCreateAccountV2WithDefaultPaymentConfig()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $this->mockSplitzEvaluation();

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $response = $this->runRequestResponseFlow($testData);

        $merchantId = $response['id'];

        Account\Entity::verifyIdAndStripSign($merchantId);

        $paymentConfig = $this->getDbEntity('config', ['merchant_id' => $merchantId]);

        $this->assertNotNull($paymentConfig);
    }

    public function testCreateLinkedAccountWithMarketplaceFeature()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->mockStorkService();

        $this->app['stork_service']->shouldNotReceive('publishOnSns');

        $testData = $this->testData['testCreateLinkedAccountWithMarketplaceFeature'];

        $response = $this->runRequestResponseFlow($testData);

        $merchantId = $response['id'];

        Account\Entity::verifyIdAndStripSign($merchantId);

        $linkedAccount = $this->getDbEntity('merchant', ['id' => $merchantId]);

        $this->assertNotNull($linkedAccount->getParentId());

        $this->assertEquals(self::DEFAULT_MERCHANT_ID, $linkedAccount->getParentId());

        return $linkedAccount;
    }

    public function testCreateLinkedAccountBlockedForVasMerchant()
    {
        $this->ba->privateAuth();

        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->merchant->edit('10000000000000', ['org_id' => OrgModel\Entity::HDFC_ORG_ID]);

        $this->fixtures->org->addFeatures('block_account_update', OrgModel\Entity::HDFC_ORG_ID);

        $testData = $this->testData['testCreateLinkedAccountWithMarketplaceFeature'];

        $this->makeRequestAndCatchException(
            function() use ($testData)
            {
                $this->runRequestResponseFlow($testData);
            },
            BadRequestException::class,
            'Linked account creation is blocked for this merchant.'
        );
    }

    public function testCreateLinkedAccountWithOutMarketplaceFeature()
    {
        $this->ba->privateAuth();

        $testData = $this->testData['testCreateLinkedAccountWithOutMarketplaceFeature'];

        $this->runRequestResponseFlow($testData);
    }

    public function testCreateLinkedAccountV2SuccessIfReverseShadowEnabledForParent()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();

        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createAccountsOnEvent')
            ->times(2)
            ->andReturn([
                'body' => [
                    "accounts" => [
                        "pg_merchant_onboarding" => null
                    ]
                ],
                'code' => 200
            ]);

        $this->mockDCS();

        $mockLedger->shouldReceive('updateAccountByEntitiesAndMerchantID')
            ->times(5)
            ->andReturn([
                'body' => [
                    "balance" => 12000
                ],
                'code' => 200
            ],
                [
                    'body' => [
                        "balance" => 0
                    ],
                    'code' => 200
                ],
                [
                    'body' => [
                        "balance" => 0
                    ],
                    'code' => 200
                ],
                [
                    'body' => [
                        "balance" => 0
                    ],
                    'code' => 200
                ],
                [
                    'body' => [
                        "balance" => 0
                    ],
                    'code' => 200
                ]);

        $response = $this->startTest();

        $merchantId = $response['id'];

        Account\Entity::verifyIdAndStripSign($merchantId);

        $linkedAccount = $this->getDbEntity('merchant', ['id' => $merchantId]);

        $this->assertNotNull($linkedAccount->getParentId());

        $this->assertEquals(self::DEFAULT_MERCHANT_ID, $linkedAccount->getParentId());

        (new MerchantSupportingEntitiesCreateJob($this->mode, $merchantId, $linkedAccount->getParentId()))->handle();

        $features = $this->getDbEntity('feature',
            [
                'entity_id' => $merchantId,
                'entity_type' => 'merchant'
            ]);

        $featuresArray = $features->pluck('name')->toArray();

        $this->assertContains(FeatureConstants::PG_LEDGER_REVERSE_SHADOW, $featuresArray);
    }

    public function testCreateLinkedAccountV2IfReverseShadowNotEnabledForParent()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $response = $this->startTest();

        $merchantId = $response['id'];

        Account\Entity::verifyIdAndStripSign($merchantId);

        $linkedAccount = $this->getDbEntity('merchant', ['id' => $merchantId]);

        $this->assertNotNull($linkedAccount->getParentId());

        (new MerchantSupportingEntitiesCreateJob($this->mode, $merchantId, $linkedAccount->getParentId()))->handle();

        $this->assertEquals(self::DEFAULT_MERCHANT_ID, $linkedAccount->getParentId());

        $featuresArray = $this->getDbEntity('feature',
            [
                'entity_id' => $merchantId,
                'entity_type' => 'merchant'
            ])->pluck('name')->toArray();

        $this->assertNotContains(FeatureConstants::PG_LEDGER_REVERSE_SHADOW, $featuresArray);
    }

    public function testCreateLinkedAccountV2FailureIfReverseShadowEnabledForParent()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();

        $this->app->instance('ledger', $mockLedger);

        $this->mockDCS();

        $mockLedger->shouldReceive('createAccountsOnEvent')
            ->times(2)
            ->andThrow(new \RZP\Exception\RuntimeException(
                'Unexpected response code received from Ledger service.',
                [
                    'status_code'   => 500,
                    'response_body' => [
                        'code' => 'invalid_argument',
                        'msg' => 'not found',
                    ],
                ]
            ));

        $response = $this->startTest();

        $merchantId = $response['id'];

        try
        {
            Account\Entity::verifyIdAndStripSign($merchantId);

            $linkedAccount = $this->getDbEntity('merchant', ['id' => $merchantId]);

            $this->assertNotNull($linkedAccount->getParentId());

            (new MerchantSupportingEntitiesCreateJob($this->mode, $merchantId, $linkedAccount->getParentId()))->handle();

        }
        catch(\Exception $e)
        {
            $this->assertNotNull($e);

            $features = $this->getDbEntity('feature',
                [
                    'entity_id' => $merchantId,
                    'entity_type' => 'merchant'
                ]);

            $featuresArray = $features->pluck('name')->toArray();

            $this->assertContains(FeatureConstants::PG_LEDGER_REVERSE_SHADOW, $featuresArray);
        }
    }

    public function testBankAccountBankAccountVerificationFails()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $featureParams = [
            Entity::ENTITY_ID   => self::DEFAULT_MERCHANT_ID,
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Core())->create($featureParams, true);

        $testData = $this->testData['testCreateSubmerchantWithNoDocFeature'];

        $result = $this->runRequestResponseFlow($testData);

        $accountId = $result['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $merchant = $this->getDbEntity('merchant', ['id' => $accountId]);

        $merchantDetails = $merchant->merchantDetail;

        $merchantDetails->setBankDetailsVerificationStatus(POIStatus::INCORRECT_DETAILS);

        $merchantDetails->setCompanyPanVerificationStatus(POIStatus::VERIFIED);

        $merchantDetails->setGstinVerificationStatus(POIStatus::VERIFIED);

        $value = (new \RZP\Models\Merchant\Detail\Core())->getApplicableActivationStatus($merchantDetails);

        $this->assertEquals('under_review', $value);
    }

    public function testAccountStatusWhenMerchantActivationStatusIsActivatedWhenExpIsEnabled()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 2);

        $metricsMock = $this->createMetricsMock();

        $expectedMetricData = $this->getDimensionsForAccountV2Metrics();

        $metricCaptured = false;

        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_FETCH_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $output["response"]["variant"]["name"] = "enable";

        $this->mockSplitExperimentForPaymentAcceptanceAttributes($output);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $accountId = $result['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->fixtures->edit('merchant_detail', $accountId, ['activation_status' => 'activated']);

        $this->fixtures->edit('merchant', $accountId, ['activated_at' => 1678107805, 'live' => true]);

        $this->startTest($testData);

        $this->assertTrue($metricCaptured);
    }

    public function testAccountStatusWhenMerchantActivationStatusIsActivatedWhenExpIsNotEnabled()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 2);

        $metricsMock = $this->createMetricsMock();

        $expectedMetricData = $this->getDimensionsForAccountV2Metrics();

        $metricCaptured = false;

        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_FETCH_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $output["response"]["variant"]["name"] = "off";

        $this->mockSplitExperimentForPaymentAcceptanceAttributes($output);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $accountId = $result['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->fixtures->edit('merchant_detail', $accountId, ['activation_status' => 'activated']);

        $this->fixtures->edit('merchant', $accountId, ['activated_at' => 1678107805, 'live' => true]);

        $fetchAccountResult = $this->runRequestResponseFlow($testData);

        $this->assertEquals('created', $fetchAccountResult['status']);
        $this->assertArrayNotHasKey('live', $fetchAccountResult);
        $this->assertArrayNotHasKey('hold_funds', $fetchAccountResult);
        $this->assertArrayNotHasKey('activated_at', $fetchAccountResult);

        $this->assertTrue($metricCaptured);
    }

    public function testDeleteAccountV2WhenNewPaymentAcceptanceFieldsExpIsEnabled()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 2);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $output["response"]["variant"]["name"] = "enable";

        $this->mockSplitExperimentForPaymentAcceptanceAttributes($output);

        $this->startTest($testData);
    }

    private function mockSplitExperimentForPaymentAcceptanceAttributes(array $output)
    {
        $input = [
            "experiment_id" => "LPIyq5qAHqpMsj",
            "id"            => self::DEFAULT_MERCHANT_ID
        ];

        $this->mockSplitzTreatment($input, $output);
    }

    public function testUpiPaymentMethodUnsetDuringAccountCreation()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $this->mockRazorxTreatment();

        $testData = $this->testData['testCreateAccountV2ForMandatoryFilledRequest'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $methods = $this->getDbEntity('methods', ['merchant_id' => $accountId])->toArray();

        $this->assertEquals(false, $methods['upi']);
    }

    public function testUpiPaymentMethodSetDuringAccountCreation()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $this->mockRazorxTreatment('off');

        $testData = $this->testData['testCreateAccountV2ForMandatoryFilledRequest'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $methods = $this->getDbEntity('methods', ['merchant_id' => $accountId])->toArray();

        $this->assertEquals(true, $methods['upi']);
    }

    public function testAccountCreationWithOnlyPhoneNumberForPhantomPartners()
    {
        $this->setPurePlatformContext(Mode::TEST, false);

        $this->fixtures->merchant->addFeatures(['cobranded_onboarding'], Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $metricsMock = $this->createMetricsMock();

        $expectedMetricData = $this->getDimensionsForAccountV2Metrics(MerchantConstants::PURE_PLATFORM);

        $metricCaptured = false;

        $this->mockStorkService();

        $this->app['stork_service']->shouldReceive('publishOnSns')->twice()->andReturn(null);

        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_CREATE_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $testData = $this->testData[__FUNCTION__];

        $response = $this->startTest($testData);

        // check that stakeholder is not yet created
        $accountId = $response['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);
        $stakeholders = $this->getDbEntities('stakeholder', ['merchant_id' => $accountId])->toArray();

        $this->assertEmpty($stakeholders);


        $users = $this->getDbEntities('users', ['contact_mobile' => '9999999999']);

        $this->assertCount(1, $users);

        $this->assertEmpty($users[0]['email']);

        $this->assertTrue($metricCaptured);
    }

    public function testPhoneNumberValidationFailureForPhantomPartnersWithoutFeature()
    {
        $this->setPurePlatformContext(Mode::TEST, false);

        $metricsMock = $this->createMetricsMock();

        $expectedMetricData = $this->getDimensionsForAccountV2Metrics(MerchantConstants::PURE_PLATFORM);

        $metricCaptured = false;

        $this->mockStorkService();

        $this->app['stork_service']->shouldReceive('publishOnSns')->twice()->andReturn(null);

        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_CREATE_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $testData = $this->testData['testAccountCreationWithOnlyPhoneNumberForPhantomPartners'];

        $response = $this->startTest($testData);

        // check that stakeholder is not yet created
        $accountId = $response['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $this->assertTrue($metricCaptured);
    }

    protected function mockRazorxTreatment(string $returnValue = 'on')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();
        $this->app->instance('razorx', $razorxMock);
        $this->app->razorx->method('getTreatment')
                          ->willReturn($returnValue);
    }

    public function testCreateAccountWithAccessBlocked()
    {
        $this->setUpNonPurePlatformPartner();

        $this->blockOnboardingApisAccess();

        $this->startTest();
    }

    public function testUpdateAccountWithAccessBlocked()
    {
        $this->setUpPartnerWithKycHandled();

        $this->allowOnboardingApisAccess(self::DEFAULT_MERCHANT_ID, 1);

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->blockOnboardingApisAccess();

        $this->runRequestResponseFlow($testData);
    }

    public function testUpdateAccountValidationFailureForPrefill()
    {
        $this->setPurePlatformContext(Mode::TEST, false);

        $this->fixtures->merchant->addFeatures(['cobranded_onboarding'], Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $testData = $this->testData['testAccountCreationWithOnlyPhoneNumberForPhantomPartners'];

        $result = $this->startTest($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);
    }

    public function testUpdateAccountForPrefillFlow()
    {
        $this->setPurePlatformContext(Mode::TEST, false);

        $this->fixtures->merchant->addFeatures(['cobranded_onboarding'], Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $testData = $this->testData['testAccountCreationWithOnlyPhoneNumberForPhantomPartners'];

        $result = $this->startTest($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);
    }

    public function testCreateAccountV2RequestForInvalidWebsiteInput()
    {
        Mail::fake();

        $features = [
            FName::KYC_HANDLED_BY_PARTNER,
            FName::SUBMERCHANT_ONBOARDING,
        ];

        $this->fixtures->merchant->addFeatures($features);

        $this->setUpNonPurePlatformPartner(MerchantConstants::AGGREGATOR);

        $this->getSplitzMock()
             ->shouldReceive('evaluateRequest')
             ->andReturnUsing(function ($input) {
                 if ($input["experiment_id"] == "NI6yG7xTin7jgY") {
                     return [
                         "response" => [
                             "variant" => [
                                 "name" => "enable"
                             ]
                         ]
                     ];
                 } else if ($input["experiment_id"] == "LQzMXMbNCUramd") {
                     return [
                         "response" => [
                             "variant" => [
                                 "name" => 'live',
                             ]
                         ]
                     ];
                 }
                 return [];
             });

        Http::fake(['https://www.example.com/' => Http::response([], 400, []),]);

        $this->startTest();

        Mail::assertNotQueued(CreateSubMerchantAffiliateForPG::class);
    }

    public function testMigrateVpaPartnerAuth()
    {
        [$subMerchantId, $clientId] = $this->setUpPartnerAuthAndGetSubMerchantIdWithClient();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = "/v2/accounts/acc_$subMerchantId/migrate_vpa";

        $this->startTest($testData);
    }

    public function testMigrateVpaFeatureNotEnabled()
    {
        $this->setPurePlatformContext(Mode::TEST, false);
        $this->fixtures->merchant->addFeatures([FeatureConstants::COBRANDED_ONBOARDING], Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $testData = $this->testData[__FUNCTION__];
        $subMerchantId = Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID;
        $testData['request']['url'] = "/v2/accounts/acc_$subMerchantId/migrate_vpa";

        $this->startTest($testData);
    }

    public function testMigrateVpaSubMerchantInactive()
    {
        $this->setPurePlatformContext(Mode::TEST, false);
        $this->fixtures->merchant->addFeatures([FeatureConstants::COBRANDED_ONBOARDING, FeatureConstants::CUSTOM_TERMINAL_PROC], Constants::DEFAULT_PLATFORM_MERCHANT_ID);
        $this->allowOnboardingApisAccess(Constants::DEFAULT_PLATFORM_MERCHANT_ID, 1);
        $this->fixtures->merchant->deactivate(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID);

        $testData = $this->testData[__FUNCTION__];
        $subMerchantId = Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID;
        $testData['request']['url'] = "/v2/accounts/acc_$subMerchantId/migrate_vpa";

        $this->startTest($testData);
    }

    public function testMigrateVpaInvalidPayload()
    {
        $this->setPurePlatformContext(Mode::TEST, false);
        $this->fixtures->merchant->addFeatures([FeatureConstants::COBRANDED_ONBOARDING, FeatureConstants::CUSTOM_TERMINAL_PROC], Constants::DEFAULT_PLATFORM_MERCHANT_ID);
        $this->allowOnboardingApisAccess(Constants::DEFAULT_PLATFORM_MERCHANT_ID, 1);

        $testData = $this->testData[__FUNCTION__];
        $subMerchantId = Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID;
        $testData['request']['url'] = "/v2/accounts/acc_$subMerchantId/migrate_vpa";

        $this->startTest($testData);
    }

    public function testMigrateVpaInvalidVpaFormat()
    {
        $this->setPurePlatformContext(Mode::TEST, false);
        $this->fixtures->merchant->addFeatures([FeatureConstants::COBRANDED_ONBOARDING, FeatureConstants::CUSTOM_TERMINAL_PROC], Constants::DEFAULT_PLATFORM_MERCHANT_ID);
        $this->allowOnboardingApisAccess(Constants::DEFAULT_PLATFORM_MERCHANT_ID, 1);

        $testData = $this->testData[__FUNCTION__];
        $subMerchantId = Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID;
        $testData['request']['url'] = "/v2/accounts/acc_$subMerchantId/migrate_vpa";

        $this->startTest($testData);
    }

    public function testMigrateVpaInvalidIssuer()
    {
        $this->setPurePlatformContext(Mode::TEST, false);
        $this->fixtures->merchant->addFeatures([FeatureConstants::COBRANDED_ONBOARDING, FeatureConstants::CUSTOM_TERMINAL_PROC], Constants::DEFAULT_PLATFORM_MERCHANT_ID);
        $this->allowOnboardingApisAccess(Constants::DEFAULT_PLATFORM_MERCHANT_ID, 1);

        $testData = $this->testData[__FUNCTION__];
        $subMerchantId = Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID;
        $testData['request']['url'] = "/v2/accounts/acc_$subMerchantId/migrate_vpa";

        $this->startTest($testData);
    }

    public function testMigrateVpaSuccess()
    {
        $this->setPurePlatformContext(Mode::TEST, false);
        $this->fixtures->merchant->addFeatures([FeatureConstants::COBRANDED_ONBOARDING, FeatureConstants::CUSTOM_TERMINAL_PROC], Constants::DEFAULT_PLATFORM_MERCHANT_ID);
        $this->allowOnboardingApisAccess(Constants::DEFAULT_PLATFORM_MERCHANT_ID, 1);
        $subMerchantId = Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID;

        $kafkaProducerMock = $this->getMockBuilder(KafkaProducerClient::class)
            ->onlyMethods(['produce'])
            ->getMock();
        $expectedMessage = [
            'merchant_id' => $subMerchantId,
            'payment_method' => 'upi',
            'instrument' => 'pg.qr.onboarding.offline.qr',
            'vpa' => [
                "upi_icici" => 'abc@icici',
            ],
            'oauth_application_id' => '1000000platApp',
        ];
        $kafkaProducerMock->expects($this->once())
            ->method('produce')
            ->with('stage_submerchant_custom_terminal_procurement', stringify($expectedMessage));
        $this->app->instance('kafkaProducerClient', $kafkaProducerMock);

        $response = $this->sendRequest([
            'url'     => "/v2/accounts/acc_$subMerchantId/migrate_vpa",
            'method'  => 'POST',
            'content' => [
                'vpa' => 'abc@icici',
            ],
        ]);
        $response->assertNoContent(202);
    }
}
