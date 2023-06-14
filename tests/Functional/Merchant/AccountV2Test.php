<?php

namespace Functional\Merchant;

use Mail;

use RZP\Services\RazorXClient;
use RZP\Models\Feature\Core;
use RZP\Models\Feature\Entity;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Repository;
use RZP\Models\Merchant\Service;
use RZP\Models\Merchant\Document;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Models\Merchant\AccountV2;
use RZP\Models\Merchant\AccountV2\Metric;
use RZP\Models\Merchant\Detail\POIStatus;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Constants\Entity as EntityConstants;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Models\Merchant\Metric as MerchantMetric;
use RZP\Tests\Functional\Fixtures\Entity\Merchant;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
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

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/AccountV2TestData.php';

        parent::setUp();


    }

    public function testCreateAccountV2ForMandatoryFilledRequest()
    {
        $this->setUpPartnerWithKycHandled();

        $response = $this->startTest();

        $accountId = $response['id'];

        $this->validateSubMerchantTagging($accountId, '10000000000000');

        $this->validateSupportingEntitiesCreation($accountId);
    }


    public function testSettleToPartnerSubmerchantMetrics()
    {
        $this->setUpPartnerWithKycHandled();

        $testData = $this->testData['testCreateAccountV2ForMandatoryFilledRequest'];

        $metricCaptured = false;

        $expectedDimensions = [
            'partner_id'     => '10000000000000',
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

    public function testCreateAccountV2WithInvalidDataRequest()
    {
        Mail::fake();

        $this->setUpPartnerWithKycHandled();

        Mail::assertNotQueued(CreateSubMerchantMail::class);

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

        $this->runRequestResponseFlow($testData);
    }

    public function testCreateAccountV2ForCompletelyFilledRegisteredBusinessRequest()
    {
        $this->setUpPartnerWithKycHandled();

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

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
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

        $this->startTest();
    }

    public function testIsAutoKycDoneForNoDoc()
    {
        $this->setUpPartnerWithKycHandled();

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
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

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
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

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
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

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
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

    public function testEditAccountV2OtherDetails()
    {
        $this->setUpPartnerWithKycHandled();

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);
    }

    public function testEditAccountWithEmptyCustomerFacingBusinessName()
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

    public function testFetchAccountV2WithNullAdditionalWebsites()
    {
        $this->setUpPartnerWithKycHandled();

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

    private function mockSplitzEvaluation()
    {
        $input = [
            "experiment_id" => "JIRYzx7YtMuB18",
            "id"            => "10000000000000",
            'request_data'  => json_encode(
                [
                    'id' => "10000000000000",
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
            "id"            => "10000000000000"
        ];

        $output ["response"]["variant"] ["name" ] = "enable";

        $this->mockSplitzTreatment($input, $output);
    }

    public function testGetValidationFieldsForNoDocOnboarding()
    {
        $this->setUpPartnerWithKycHandled();

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
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

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
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

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
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

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
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

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $result['id'];

        $this->startTest($testData);
    }

    public function testInstantActivationTagAppendedOnSubM()
    {
        $this->setUpPartnerWithKycHandled();

        $testData = $this->testData['testCreateAccountV2ForCompletelyFilledRequest'];

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
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

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding'
        ];

        (new Core())->create($featureParams, true);

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
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

        $this->assertEquals('10000000000000', $linkedAccount->getParentId());

        return $linkedAccount;
    }

    public function testCreateLinkedAccountWithOutMarketplaceFeature()
    {
        $this->ba->privateAuth();

        $testData = $this->testData['testCreateLinkedAccountWithOutMarketplaceFeature'];

        $this->runRequestResponseFlow($testData);
    }

    public function testBankAccountBankAccountVerificationFails()
    {
        $this->setUpPartnerWithKycHandled();

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
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
            "id"            => "10000000000000"
        ];

        $this->mockSplitzTreatment($input, $output);
    }

    public function testUpiPaymentMethodUnsetDuringAccountCreation()
    {
        $this->setUpPartnerWithKycHandled();

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

        $this->mockRazorxTreatment('off');

        $testData = $this->testData['testCreateAccountV2ForMandatoryFilledRequest'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $methods = $this->getDbEntity('methods', ['merchant_id' => $accountId])->toArray();

        $this->assertEquals(true, $methods['upi']);
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
}
