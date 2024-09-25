<?php

namespace Unit\Models\Merchant\Detail;

use App;
use Queue;
use Config;
use Mockery;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Tests\Traits\TestsStorkServiceRequests;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Services\Mock\HarvesterClient;
use RZP\Services\RazorXClient;
use RZP\Tests\Traits\MocksSplitz;
use Illuminate\Support\Facades\Mail;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Mail\Merchant\MerchantOnboardingEmail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\Document;
use RZP\Models\Feature\Constants as FeatureConstant;
use RZP\Models\Merchant\RazorxTreatment;

class BankingCoreTest extends TestCase
{
    protected $repo;
    protected $app;
    protected $config;
    protected $merchant;
    protected $pgosProxyController;

    use DbEntityFetchTrait;
    use MocksSplitz;
    use TestsStorkServiceRequests;

    protected function setUp(): void
    {

        parent::setUp();
        $this->app = App::getFacadeRoot();
        $this->repo = $this->app['repo'];

        $this->config = \Illuminate\Support\Facades\App::getFacadeRoot()['config'];

        Config::set('services.kafka.producer.mock', true);

        $this->merchant = $this->app['basicauth']->getMerchant();

        $this->pgosProxyController = Mockery::mock('RZP\Http\Controllers\MerchantOnboardingProxyController');
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

    private function mockPinot(string $merchantId, int $amount)
    {
        $pinotService = $this->getMockBuilder(HarvesterClient::class)
                             ->setConstructorArgs([$this->app])
                             ->setMethods(['getDataFromPinot'])
                             ->getMock();

        $this->app->instance('eventManager', $pinotService);

        $dataFromPinot = ['merchant_id' => $merchantId, "amount" => $amount * 100, "transacted_merchants_count" => 1];

        $pinotService->method('getDataFromPinot')
                     ->willReturn([$dataFromPinot]);
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

        $this->mockPinot($merchantId, $amount);
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

        $this->mockPinot($merchantId, $amount);
    }

    protected function createAndFetchFixtures($customMerchantAttributes, $customVerificationDetailAttributes, $customBvsDetails)
    {
        $defaultMerchantAttributes = [
            'promoter_pan'            => 'BRRPK8070K',
            'promoter_pan_name'       => 'kakarla vasanthi',
            'company_pan'             => 'ABCCD1234A',
            'business_name'           => 'xyz',
            'bank_account_number'     => '1234567890',
            'bank_branch_ifsc'        => 'UTIB0002953',
            'bank_account_name'       => 'XYZ',
            'company_cin'             => 'U67190TN2014PTC096971',
            'gstin'                   => '01AADCB1234M1ZX',
        ];

        $merchantDetail = $this->fixtures->create(
            'merchant_detail:valid_fields',
            array_merge($defaultMerchantAttributes, $customMerchantAttributes));

        $mid = $merchantDetail->getId();

        $defaultVerificationDetailAttributes = [
            'merchant_id'          => $mid,
            'artefact_type'        => 'gstin',
            'artefact_identifier'  => 'doc',
        ];

        $verificationDetail = $this->fixtures->create(
            'merchant_verification_detail',
            array_merge($defaultVerificationDetailAttributes, $customVerificationDetailAttributes));

        $defaultBvsDetails = [
            'owner_type'        => 'merchant',
            'owner_id'          => $mid,
            'validation_status' => BvsValidationConstants::CAPTURED,
            'platform'          => 'pg',
            'created_at'        => Carbon::now()->subDays(2)->getTimestamp()
        ];

        $bvsValidation = $this->fixtures->create(
            'bvs_validation',
            array_merge($defaultBvsDetails, $customBvsDetails));

        $this->fixtures->create('merchant_document',
            [
                'merchant_id'   => $mid,
                'validation_id' => $bvsValidation->getValidationId(),
                'document_type' => Document\Type::AADHAR_FRONT,
                'file_store_id' => '123123',
            ]);

        $this->fixtures->create('merchant_document',
            [
                'merchant_id'   => $mid,
                'validation_id' => $bvsValidation->getValidationId(),
                'document_type' => Document\Type::AADHAR_BACK,
                'file_store_id' => '123123'
            ]);

        return [
            'merchant_detail'    => $merchantDetail,
            'verificationDetail' => $verificationDetail,
            'bvsValidation'      => $bvsValidation
        ];
    }

    protected function mockBvsService(string $bvsResponse)
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', $bvsResponse);
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

    // sets razorx mock based on input array of key value pair of features and their expected values
    protected function setMockRazorxTreatment(array $razorxTreatment, string $defaultBehaviour = 'off')
    {
        // Mock Razorx
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['getTreatment'])
                            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                            ->will($this->returnCallback(
                              function ($mid, $feature) use ($razorxTreatment, $defaultBehaviour)
                              {
                                if (array_key_exists($feature, $razorxTreatment) === true)
                                {
                                    return $razorxTreatment[$feature];
                                }

                                return strtolower($defaultBehaviour);
                            }));
    }

    public function testActivatedMccPendingActivationStatusWithoutCustomOnboardingViaMailgun()
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

        //verify stork payload
        $queuedEmails =Mail::queued(MerchantOnboardingEmail::class);

        $this->assertCount(2,$queuedEmails);
        $shouldSendEmailViaStork = $queuedEmails->get(0)->shouldSendEmailViaStork();
        $getParamsForStork = $queuedEmails->get(0)->getParamsForStork();

        $this->assertArrayHasKey('template_name', $getParamsForStork);
        $this->assertArrayHasKey('template_namespace', $getParamsForStork);
        $this->assertArrayHasKey('params', $getParamsForStork);
        $this->assertArrayHasKey('merchant', $getParamsForStork['params']);
        $this->assertArrayHasKey('name', $getParamsForStork['params']['merchant']);
        $this->assertArrayHasKey('business_website', $getParamsForStork['params']['merchant']);
        $this->assertArrayHasKey('org', $getParamsForStork['params']);
        $this->assertArrayHasKey('login_logo_url', $getParamsForStork['params']['org']);
        $this->assertArrayHasKey('payment_url', $getParamsForStork['params']);
        $this->assertArrayHasKey('isCustomOnboardingEmail', $getParamsForStork['params']);

        $this->assertEquals($getParamsForStork['params']['isCustomOnboardingEmail'], false);
        $this->assertEquals($shouldSendEmailViaStork, false);

    }

    public function testActivatedMccPendingActivationStatusWithCustomOnboardingViaStork()
    {
        Mail::fake();
        $this->fixtures->org->addFeatures([FeatureConstant::CUSTOM_ONBOARDING_EMAILS],"100000razorpay");

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

        $this->mockSplitzExperiment(['response' => ['variant' => ['name' => 'enable', ]]]);

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

        //verify stork payload
        $queuedEmails =Mail::queued(MerchantOnboardingEmail::class);

        $this->assertCount(2,$queuedEmails);
        $shouldSendEmailViaStork = $queuedEmails->get(0)->shouldSendEmailViaStork();
        $getParamsForStork = $queuedEmails->get(0)->getParamsForStork();

        $this->assertArrayHasKey('template_name', $getParamsForStork);
        $this->assertArrayHasKey('template_namespace', $getParamsForStork);
        $this->assertArrayHasKey('params', $getParamsForStork);
        $this->assertArrayHasKey('merchant', $getParamsForStork['params']);
        $this->assertArrayHasKey('name', $getParamsForStork['params']['merchant']);
        $this->assertArrayHasKey('business_website', $getParamsForStork['params']['merchant']);
        $this->assertArrayHasKey('org', $getParamsForStork['params']);
        $this->assertArrayHasKey('login_logo_url', $getParamsForStork['params']['org']);
        $this->assertArrayHasKey('payment_url', $getParamsForStork['params']);
        $this->assertArrayHasKey('isCustomOnboardingEmail', $getParamsForStork['params']);

        $this->assertEquals($getParamsForStork['params']['isCustomOnboardingEmail'], true);
        $this->assertEquals($shouldSendEmailViaStork, true);

    }

   public function testActivatedMccPendingActivationStatusWithoutCustomOnboarding()
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
        $activatedMccPendingSuccessData = $queuedEmails->get(0)->getData();
        $activatedMccPendingActionRequiredData = $queuedEmails->get(1)->getData();
        $this->assertContains($queuedEmails->get(0)->getTemplate(),$expectedEmails);
        $this->assertContains($queuedEmails->get(1)->getTemplate(),$expectedEmails);
        $this->assertArrayHasKey('isCustomOnboardingEmail', $activatedMccPendingSuccessData);
        $this->assertEquals($activatedMccPendingSuccessData['isCustomOnboardingEmail'], false);
        $this->assertArrayHasKey('isCustomOnboardingEmail', $activatedMccPendingActionRequiredData);
        $this->assertEquals($activatedMccPendingActionRequiredData['isCustomOnboardingEmail'], false);

    }

    public function testActivatedMccPendingActivationStatusWithCustomOnboarding()
    {
        Mail::fake();

        $this->fixtures->org->addFeatures([FeatureConstant::CUSTOM_ONBOARDING_EMAILS],"100000razorpay");

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
        $activatedMccPendingSuccessData = $queuedEmails->get(0)->getData();
        $activatedMccPendingActionRequiredData = $queuedEmails->get(1)->getData();
        $this->assertContains($queuedEmails->get(0)->getTemplate(),$expectedEmails);
        $this->assertContains($queuedEmails->get(1)->getTemplate(),$expectedEmails);
        $this->assertArrayHasKey('isCustomOnboardingEmail', $activatedMccPendingSuccessData);
        $this->assertEquals($activatedMccPendingSuccessData['isCustomOnboardingEmail'], true);
        $this->assertArrayHasKey('isCustomOnboardingEmail', $activatedMccPendingActionRequiredData);
        $this->assertEquals($activatedMccPendingActionRequiredData['isCustomOnboardingEmail'], true);

    }

}
