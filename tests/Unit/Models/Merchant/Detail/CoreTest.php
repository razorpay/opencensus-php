<?php

namespace Unit\Models\Merchant\Detail;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Merchant\Escalations;
use RZP\Services\RazorXClient;
use Illuminate\Support\Facades\Mail;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Mail\Merchant\MerchantOnboardingEmail;
use RZP\Services\Segment\SegmentAnalyticsClient;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Admin\Org\Entity as ORG_ENTITY;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Detail\SelectiveRequiredFields;
use RZP\Models\Merchant\Detail\Constants as DetailConstant;

class CoreTest extends TestCase
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CoreTestData.php';

        parent::setUp();
    }

    protected function createAndFetchMocks()
    {
        $mockMC = $this->getMockBuilder(MerchantCore::class)
            ->setMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $mockMC->expects($this->any())
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        return [
            "merchantCoreMock"    => $mockMC
        ];
    }

    private function createTransaction(string $merchantId, string $type, int $amount, int $createdAt = null)
    {
        if($createdAt === null)
        {
            $createdAt = Carbon::now()->getTimestamp();
        }

        $transaction = $this->fixtures->on('live')->create('transaction', [
            'type'          => $type,
            'amount'        => $amount * 100,   // in paisa
            'merchant_id'   => $merchantId,
            'created_at'    => $createdAt
        ]);
    }

    private function createPayment(string $merchantId, int $amount, int $createdAt = null)
    {
        if($createdAt === null)
        {
            $createdAt = Carbon::now()->getTimestamp();
        }

        $transaction = $this->fixtures->on('live')->create('payment', [
            'amount'        => $amount * 100,   // in paisa
            'merchant_id'   => $merchantId,
            'created_at'    => $createdAt
        ]);
    }

    public function testSegmentEventPushForFirstTransaction()
    {
        $this->createAndFetchMocks();

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
            ->setMethods(['pushIdentifyAndTrackEvent'])
            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(1))
            ->method('pushIdentifyAndTrackEvent')
            ->willReturn(true);

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        $this->createTransaction($merchantId, 'payment', 10000);
        $this->createPayment($merchantId, 10000);

        (new Escalations\Core)->handleMtuSegmentEvent();
    }

    public function testSegmentEventSkipIfNotFirstTransaction()
    {
        $this->createAndFetchMocks();

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
            ->setMethods(['pushIdentifyAndTrackEvent'])
            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(0))
            ->method('pushIdentifyAndTrackEvent')
            ->willReturn(true);

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        // Create transaction that is 4 days old (since cron picks last 3 days transacted merchants)
        $this->createTransaction(
            $merchantId, 'payment', 10000, Carbon::now()->subDays(4)->getTimestamp());
        $this->createPayment($merchantId, 10000, Carbon::now()->subDays(4)->getTimestamp());

        // Create new transaction
        $this->createTransaction($merchantId, 'payment', 10000);
        $this->createPayment($merchantId, 10000);

        (new Escalations\Core)->handleMtuSegmentEvent();
    }

    public function testSegmentEventIfTwoTransactionsDuringSameTime()
    {
        $this->createAndFetchMocks();

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
            ->setMethods(['pushIdentifyAndTrackEvent'])
            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(1))
            ->method('pushIdentifyAndTrackEvent')
            ->willReturn(true);

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        // Create new 2 transactions
        $this->createTransaction($merchantId, 'payment', 10000);
        $this->createPayment($merchantId, 10000);

        $this->createTransaction($merchantId, 'payment', 10000);
        $this->createPayment($merchantId, 10000);

        (new Escalations\Core)->handleMtuSegmentEvent();
    }


    public function testValidationFieldsIfAadhaarEsignVerificationIsDone()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'                         => '11',
        ]);

        $mid = $merchantDetail->getId();

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id'                          => $mid,
            'aadhaar_esign_status'                 => 'verified'
        ]);

        $mocks = $this->createAndFetchMocks();
        $core = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        [$validationFields, $validationSelectiveRequiredFields, $validationOptionalFields] = $core->getValidationFields($merchantDetail);

        $this->assertFalse(isset($validationSelectiveRequiredFields[SelectiveRequiredFields::POA_DOCUMENTS]));
    }

    public function testValidationFieldsIfAadhaarEsignVerificationIsNotDone()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'                         => '11',
        ]);

        $mid = $merchantDetail->getId();

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id'                          => $mid,
        ]);

        $mocks = $this->createAndFetchMocks();
        $core = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        [$validationFields, $validationSelectiveRequiredFields, $validationOptionalFields] = $core->getValidationFields($merchantDetail);

        $this->assertTrue(isset($validationSelectiveRequiredFields[SelectiveRequiredFields::POA_DOCUMENTS]));
    }

    public function testValidationFieldsIfAadhaarIsNotLinked()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'                         => '11',
        ]);

        $mid = $merchantDetail->getId();

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id'         => $mid,
            'aadhaar_linked'      => 0
        ]);

        $mocks = $this->createAndFetchMocks();
        $core = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        [$validationFields, $validationSelectiveRequiredFields, $validationOptionalFields] = $core->getValidationFields($merchantDetail);

        $this->assertTrue(isset($validationSelectiveRequiredFields[SelectiveRequiredFields::POA_DOCUMENTS]));
    }

    public function testBusinessRegisteredStateCodeValidation()
    {
        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_INVALID_STATE_CODE);

        $input = [
            DetailEntity::BUSINESS_REGISTERED_STATE => 'Maharashtra'
        ];

        (new DetailEntity)->build($input);
    }

    public function testIsMerchantTncApplicableSuccess()
    {
        $core = new DetailCore();

        $merchantId = '2cXSLlUU8V9sXl';

        $this->fixtures->create('org',[
            'id' => ORG_ENTITY::AXIS_ORG_ID,
        ]);

        $merchant = $this->fixtures->create('merchant',[
            'org_id'      => ORG_ENTITY::AXIS_ORG_ID,
            'id'          => $merchantId,
        ]);

        $this->fixtures->create('merchant_detail',[
            'merchant_id'      => $merchant->getId(),
        ]);

        $this->mockRazorxTreatment();

        $isMerchantTncApplicable = $core->isMerchantTncApplicable($merchant);

        $this->assertEquals(true, $isMerchantTncApplicable);
    }

    public function testBusinessRegisteredStateCodeValidationOnInvalid2DigitCode()
    {
        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_INVALID_STATE_CODE);

        $input = [
            DetailEntity::BUSINESS_REGISTERED_STATE => 'XT'     // invalid state code
        ];

        (new DetailEntity)->build($input);
    }

    public function testBusinessRegisteredStateCodeValidationSuccess()
    {
        $input = [
            DetailEntity::BUSINESS_REGISTERED_STATE => 'MH'     // valid state code
        ];

        $merchantDetail = (new DetailEntity)->build($input);

        $this->assertEquals($merchantDetail->getBusinessRegisteredState(), 'MH');
    }

    public function testActivationProgressAfterFirstLogin()
    {
        $this->mockRazorxTreatment();

        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category' => 'financial_services',
            'business_subcategory' => 'accounting',
        ]);

        $merchant = $merchantDetails->merchant;

        $response = $core->setVerificationDetails($merchantDetails, $merchant, []);

        $this->assertEquals(10, $response['verification']['activation_progress']);
    }

    public function testGroupBankDetails()
    {
        $core = new DetailCore();

        $testData = $this->testData['testGroupBankDetailsInput'];

        $merchantDetails = $this->fixtures->create('merchant_detail',$testData);

        $output= $core->getUpdatedKycClarificationReasons(
            [],
            $merchantDetails->getId()
        );

        $expectedOutput = $this->testData['testGroupBankDetailsOutput'];

        $this->assertEquals($expectedOutput, $output);
    }

    public function testGroupPromoterPanDetails()
    {
        $core = new DetailCore();

        $testData = $this->testData['testGroupedPromoterPanDetailsInput'];

        $merchantDetails = $this->fixtures->create('merchant_detail',$testData);

        $output= $core->getUpdatedKycClarificationReasons(
            [],
            $merchantDetails->getId()
        );

        $expectedOutput = $this->testData['testGroupedPromoterPanDetailsOutput'];

        $this->assertEquals($expectedOutput, $output);
    }

    public function testGroupCompanyPanDetails()
    {
        $core = new DetailCore();

        $testData = $this->testData['testGroupedCompanyPanDetailsInput'];

        $merchantDetails = $this->fixtures->create('merchant_detail',$testData);

        $output= $core->getUpdatedKycClarificationReasons(
            [],
            $merchantDetails->getId()
        );

        $expectedOutput = $this->testData['testGroupedCompanyPanDetailsOutput'];

        $this->assertEquals($expectedOutput, $output);
    }

    public function testAddToExistingClarificationReasonV2()
    {
        $core = new DetailCore();

        $testData = $this->testData['existingClarificationReasonV2Data'];

        $merchantDetails = $this->fixtures->create('merchant_detail',$testData);

        $newClarificationReasonV2 = $this->testData['newClarificationReasonV2Data'];

        $fixedTime = (new Carbon())->timestamp(1583548200);

        Carbon::setTestNow($fixedTime);

        $this->fixtures->create('state', [
            'entity_id'   =>  $merchantDetails->getId(),
            'entity_type' => 'merchant_detail',
            'name'        => 'under_review'
        ]);

        $this->fixtures->create('state', [
            'entity_id'   =>  $merchantDetails->getId(),
            'entity_type' => 'merchant_detail',
            'name'        => 'needs_clarification'
        ]);

        $this->fixtures->create('state', [
            'entity_id'   =>  $merchantDetails->getId(),
            'entity_type' => 'merchant_detail',
            'name'        => 'under_review'
        ]);

        $output= $core->getUpdatedKycClarificationReasons(
            $newClarificationReasonV2,
            $merchantDetails->getId(),
            DetailConstant::ADMIN
        );

        $expectedOutput = $this->testData['updatedClarificationReasonV2Output'];

        $this->assertEquals($expectedOutput, $output);
    }

    public function testActivationProgressL1Filled()
    {
        $this->mockRazorxTreatment();

        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category' => 'financial_services',
            'business_subcategory' => 'accounting',
            'activation_form_milestone' => 'L1'
        ]);

        $merchant = $merchantDetails->merchant;

        $response = $core->setVerificationDetails($merchantDetails, $merchant, []);

        $this->assertEquals(60, $response['verification']['activation_progress']);
    }

    public function testActivationProgressL2Filled()
    {
        $this->mockRazorxTreatment();

        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category' => 'financial_services',
            'business_subcategory' => 'accounting',
            'activation_form_milestone' => 'L2'
        ]);

        $merchant = $merchantDetails->merchant;

        $response = $core->setVerificationDetails($merchantDetails, $merchant, []);

        $this->assertEquals(80, $response['verification']['activation_progress']);
    }

    public function testActivationProgressActivatedMCCPending()
    {
        $this->mockRazorxTreatment();

        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_form_milestone' => 'L2',
            'activation_status'         => 'activated_mcc_pending',
        ]);

        $this->fixtures->create('state', [
            'entity_id'   => $merchantDetails->getId(),
            'entity_type' => 'merchant_detail',
            'name'        => 'activated_mcc_pending',
        ]);

        $merchant = $merchantDetails->merchant;

        $response = $core->setVerificationDetails($merchantDetails, $merchant, []);

        $this->assertEquals(90, $response['verification']['activation_progress']);
    }

    public function testActivationProgressActivated()
    {
        $this->mockRazorxTreatment();

        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_form_milestone' => 'L2',
            'activation_status'         => 'activated',
        ]);

        $merchant = $merchantDetails->merchant;

        $response = $core->setVerificationDetails($merchantDetails, $merchant, []);

        $this->assertEquals(100, $response['verification']['activation_progress']);
    }

    public function testActivationProgressTncGenerated()
    {
        $this->mockRazorxTreatment();

        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_form_milestone' => 'L2',
        ]);

        $this->fixtures->create('merchant_tnc', [
            'merchant_id'           => $merchantDetails->getId(),
            'deliverable_type'      => 'services',
            'shipping_period'       => '2 hours',
            'refund_request_period' => '29 days',
            'refund_process_period' => '1 day',
        ]);

        $merchant = $merchantDetails->merchant;

        $response = $core->setVerificationDetails($merchantDetails, $merchant, []);

        $this->assertEquals(85, $response['verification']['activation_progress']);
    }

    public function testL2RequiresPOI()
    {
        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J'
        ]);

        $response = $core->isPOIVerificationRequiredForL2($merchantDetails, ['promoter_pan'=>'AAAPA1234J']);

        $this->assertEquals(false, $response);
    }

    public function testL2RequiresPoiVerificationPending()
    {
        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'pending',
            'promoter_pan'              => 'AAAPA1234J'
        ]);

        $response = $core->isPOIVerificationRequiredForL2($merchantDetails, ['promoter_pan'=>'AAAPA1234J']);

        $this->assertEquals(true, $response);
    }

    public function testL2RequiresPoiNew()
    {
        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J'
        ]);

        $response = $core->isPOIVerificationRequiredForL2($merchantDetails, ['promoter_pan'=>'AAAPA6969J']);

        $this->assertEquals(true, $response);
    }

    public function testApplicableActivationStatus()
    {
        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'          => 'under_review',
        ]);

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testActivatedMccPendingActivationStatus()
    {
        Mail::fake();
        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'          => 'under_review',
            'submitted'=>true,
            'business_Website'=> null
        ]);

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED_MCC_PENDING,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);
        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant,$activationStatusData,$merchantDetails->merchant);
        //verify email has been sent
        $expectedEmails=['emails.merchant.onboarding.activated_mcc_pending_success',
                         'emails.merchant.onboarding.activated_mcc_pending_action_required'];
        $queuedEmails =Mail::queued(MerchantOnboardingEmail::class);
        $this->assertCount(2,$queuedEmails);
        $this->assertContains($queuedEmails->get(0)->getTemplate(),$expectedEmails);
        $this->assertContains($queuedEmails->get(1)->getTemplate(),$expectedEmails);


    }
    public function testActivatedMccPendingActivationStatusPartnership()
    {
        Mail::fake();
        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'          => 'under_review',
            'submitted'=>true,
            'business_Website'=> null
        ]);

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED_MCC_PENDING,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);
        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant,$activationStatusData,$merchantDetails->merchant);
        //verify email has been sent
        $expectedEmails=['emails.merchant.onboarding.activated_mcc_pending_success',
                         'emails.merchant.onboarding.activated_mcc_pending_action_required'];
        $queuedEmails =Mail::queued(MerchantOnboardingEmail::class);
        $this->assertCount(2,$queuedEmails);
        $this->assertContains($queuedEmails->get(0)->getTemplate(),$expectedEmails);
        $this->assertContains($queuedEmails->get(1)->getTemplate(),$expectedEmails);


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
