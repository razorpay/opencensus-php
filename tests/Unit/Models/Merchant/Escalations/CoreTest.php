<?php


namespace Unit\Models\Merchant\Escalations;

use DB;
use Mail;
use Queue;
use Mockery;
use RZP\Models\State;
use RZP\Constants\Mode;
use RZP\Services\CareServiceClient;
use RZP\Services\Dcs\Configurations\Service as DcsConfigService;
use RZP\Services\Dcs\Features\Service as DCSService;
use RZP\Services\Mock\HarvesterClient;
use RZP\Services\Mock\DataLakePresto;
use RZP\Mail\Merchant\MerchantOnboardingEmail;
use RZP\Notifications\Onboarding\Events;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Escalations;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Merchant\Escalations\Actions;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Detail as MerchantDetail;
use RZP\Services\Mock\DruidService as MockDruidService;

class CoreTest extends TestCase
{
    use TestsWebhookEvents;
    use DbEntityFetchTrait;

    const PINOT = 'pinot';

    const DATALAKE = 'datalake';

    public function testNoEscalationTriggeredIfMerchantNotInOpenState()
    {
        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'activation_status' => 'activated'
        ]);
        $merchantId     = $merchantDetail->getMerchantId();

        $this->mockDCS();

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $escalation = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');

        // Verify no escalation is triggered for the merchant
        $this->assertEmpty($escalation);
    }

    public function testNoEscalationTriggeredIfMerchantPaymentIsBelowThreshold()
    {
        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'activation_status' => 'under_review'
        ]);
        $merchantId     = $merchantDetail->getMerchantId();

        $this->createTransaction($merchantId, 'payment', 900);

        $this->mockDCS();

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $escalation = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');

        // Verify no escalation is triggered for the merchant
        $this->assertEmpty($escalation);
    }

    private function mockDCS($hardlimit = 0,$entityId=null,$orgexists = false)
    {
        $dcsConfigService = $this->getMockBuilder( DcsConfigService::class)
            ->setConstructorArgs([$this->app])
            ->getMock();


        $this->app->instance('dcs_config_service', $dcsConfigService);

        if ($orgexists){
            $this->app->dcs_config_service->method('fetchEntityIdsWithValueByConfigNameAndFieldNameFromDcs')
                ->willReturn([
                    $entityId => true,
                ]);
        }else{
            $this->app->dcs_config_service->method('fetchEntityIdsWithValueByConfigNameAndFieldNameFromDcs')->willReturn([]);
        }

        if ($hardlimit !=0){
            $this->app->dcs_config_service->method('fetchConfiguration')
                ->willReturn([Escalations\Constants::ASSIGN_CUSTOM_HARD_LIMIT => true,Escalations\Constants::CUSTOM_TRANSACTION_LIMIT_FOR_KYC_PENDING => $hardlimit]);
        }else{
            $this->app->dcs_config_service->method('fetchConfiguration')->willReturn([Escalations\Constants::ASSIGN_CUSTOM_HARD_LIMIT => false]);
        }

    }

    private function mockDCSThrowException()
    {
        $dcsConfigService = $this->getMockBuilder( DcsConfigService::class)
            ->setConstructorArgs([$this->app])
            ->getMock();


        $this->app->instance('dcs_config_service', $dcsConfigService);

        $this->app->dcs_config_service->method('fetchEntityIdsWithValueByConfigNameAndFieldNameFromDcs')->willThrowException(new \Exception('Mocked exception'));
    }

    public function mockfetchCustomHardLimitConfig($hardlimit = 0) {
        $dcsConfigService = $this->getMockBuilder( DcsConfigService::class)
            ->setConstructorArgs([$this->app])
            ->getMock();


        $this->app->instance('dcs_config_service', $dcsConfigService);

        if ($hardlimit !=0){
            $this->app->dcs_config_service->method('fetchConfiguration')
                ->willReturn([Escalations\Constants::ASSIGN_CUSTOM_HARD_LIMIT => true,Escalations\Constants::CUSTOM_TRANSACTION_LIMIT_FOR_KYC_PENDING => $hardlimit]);
        }else{
            $this->app->dcs_config_service->method('fetchConfiguration')->willReturn([Escalations\Constants::ASSIGN_CUSTOM_HARD_LIMIT => false]);
        }
    }

    /**
     * Scenario:
     * -1 merchant is moved to activated mcc pending state
     * -2 merchant has accepted payment of worth 1K and thus escalation has triggered
     * -3 merchant has again accepted another payment of some amount (so that 15K isn't breached)
     * -4 Since escalation was already triggered in step 2, new escalation should not be triggered
     *    when cron is ran again for the merchant
     */
    public function test_already_escalated_1K_milestone_soft_limt()
    {
        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $this->createAndFetchMocks(true);

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('soft_limit');
        $merchantId = $merchantDetail->getMerchantId();

        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 500, self::DATALAKE);
        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 500);

        $this->mockDCS();

        $existingEscalation = $this->addEscalation('soft_limit', 100000);

        [$triggered, $reason] = (new Escalations\Handler)->triggerPaymentEscalation(
            $merchantId, 100000, [$existingEscalation->toArray()]);

        $this->assertFalse($triggered);
    }

    public function testEscalation_5K_milestone_L1()
    {
        $this->app->instance("rzp.mode", Mode::LIVE);
        Mail::fake();

        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $this->createAndFetchMocks(true);

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('L1');

        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 2500, self::DATALAKE);
        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 2500);

        $this->mockDCS();

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $escalation = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');

        $this->verifyEscalationAndAction('L1', 500000);
    }

    public function testEscalation_10K_milestone_L1()
    {
        $this->app->instance("rzp.mode", Mode::LIVE);
        Mail::fake();

        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $this->createAndFetchMocks(true);

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('L1');

        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 5000, self::DATALAKE);
        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 5000);

        $this->addEscalation('L1', 500000);

        $this->mockDCS();

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $this->verifyEscalationAndAction('L1', 1000000);
    }

    public function testEscalation_15K_milestone_L1()
    {
        $this->app->instance("rzp.mode", Mode::LIVE);
        Mail::fake();

        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $this->createAndFetchMocks(true);

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('L1');

        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 5000, self::DATALAKE);
        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 10000);

        $this->addEscalation('L1', 500000);
        $this->addEscalation('L1', 1000000);

        $this->mockDCS();

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $this->verifyEscalationAndAction('L1', 1500000);
    }

    public function testEscalationHardLimitLevel4()
    {
        $limit = 50000;

        $this->app->instance("rzp.mode", Mode::LIVE);

        Mail::fake();

        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $this->createAndFetchMocks(true);

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('hard_limit');

        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', $limit, self::DATALAKE);
        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 200);

        $this->fixtures->create('state', [
            State\Entity::ENTITY_ID     => $merchantDetail->getMerchantId(),
            State\Entity::NAME          => MerchantDetail\Status::ACTIVATED_MCC_PENDING,
            State\Entity::ENTITY_TYPE   => 'merchant_detail'
        ]);

        $this->mockDCS();

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $merchant = $this->getDbEntityById('merchant', $merchantDetail->getMerchantId());

        $this->assertTrue($merchant->getAttribute(MerchantEntity::HOLD_FUNDS));

        $this->verifyEscalationAndAction('hard_limit_level_4', $limit * 100);
    }

    public function testEscalationNeedsClarificationHardLimitLevel4()
    {
        $limit = 50000;

        $this->app->instance("rzp.mode", Mode::LIVE);

        Mail::fake();

        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $this->createAndFetchMocks(true);

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('hard_limit');

        $merchantId = $merchantDetail->getMerchantId();

        $this->createTransaction($merchantId, 'payment', $limit, self::DATALAKE);
        $this->createTransaction($merchantId, 'payment', 1);

        $this->fixtures->create('state', [
            State\Entity::ENTITY_ID     => $merchantId,
            State\Entity::NAME          => MerchantDetail\Status::ACTIVATED_MCC_PENDING,
            State\Entity::ENTITY_TYPE   => 'merchant_detail'
        ]);

        $this->fixtures->edit('merchant_detail', $merchantId, [
            MerchantDetail\Entity::ACTIVATION_STATUS => MerchantDetail\Status::NEEDS_CLARIFICATION
        ]);

        $this->mockDCS();

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertTrue($merchant->getAttribute(MerchantEntity::HOLD_FUNDS));

        $this->assertEquals(MerchantDetail\Status::NEEDS_CLARIFICATION, $merchantDetail->getAttribute(MerchantDetail\Entity::ACTIVATION_STATUS));

        $this->verifyEscalationAndAction('hard_limit_level_4', $limit * 100);
    }

    public function testEscalationUnderReviewHardLimitLevel4()
    {
        $limit = 50000;

        $this->app->instance("rzp.mode", Mode::LIVE);

        Mail::fake();

        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $this->createAndFetchMocks(true);

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('hard_limit');

        $merchantId = $merchantDetail->getMerchantId();

        $this->createTransaction($merchantId, 'payment', $limit, self::DATALAKE);
        $this->createTransaction($merchantId, 'payment', 200);

        $this->fixtures->create('state', [
            State\Entity::ENTITY_ID     => $merchantId,
            State\Entity::NAME          => MerchantDetail\Status::ACTIVATED_MCC_PENDING,
            State\Entity::ENTITY_TYPE   => 'merchant_detail'
        ]);

        $this->fixtures->edit('merchant_detail', $merchantId, [
            MerchantDetail\Entity::ACTIVATION_STATUS => MerchantDetail\Status::UNDER_REVIEW
        ]);

        $this->mockDCS();

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertTrue($merchant->getAttribute(MerchantEntity::HOLD_FUNDS));

        $this->assertEquals(MerchantDetail\Status::UNDER_REVIEW, $merchantDetail->getAttribute(MerchantDetail\Entity::ACTIVATION_STATUS));

        $this->verifyEscalationAndAction('hard_limit_level_4', $limit * 100);
    }

    public function testEscalation10kMilestoneTimeBoundFalseFilterLinkedAccount()
    {
        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $this->createAndFetchMocks(true);

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'activation_status'         => 'under_review',
            'activation_form_milestone' => 'L1'
        ]);

        $merchantId = $merchantDetail->getMerchantId();

        $this->fixtures->merchant->createAccount('100DemoAccount');

        $this->fixtures->on('live')->edit('merchant', $merchantId, [
            'parent_id' => '100DemoAccount'
        ]);

        $this->createTransaction($merchantId, 'payment', 5000, self::DATALAKE);
        $this->createTransaction($merchantId, 'payment', 5000);

        $this->mockDCS();

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $escalation = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');

        // Verify no escalation is triggered for the merchant
        $this->assertEmpty($escalation);
    }

    public function testEscalation10kMilestoneTimeBoundFalseFilterNonRazorpayOrg()
    {
        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $this->createAndFetchMocks(true);

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'activation_status'         => 'under_review',
            'activation_form_milestone' => 'L1'
        ]);

        $merchantId = $merchantDetail->getMerchantId();

        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->on('live')->edit('merchant', $merchantId, [
            'org_id' => Org::HDFC_ORG
        ]);

        $this->createTransaction($merchantId, 'payment', 5000, self::DATALAKE);
        $this->createTransaction($merchantId, 'payment', 5000);

        $this->mockDCS();

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $escalation = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');

        // Verify no escalation is triggered for the merchant
        $this->assertEmpty($escalation);
    }

    public function testEscalation10kMilestoneTimeBoundTrueFilterLinkedAccount()
    {
        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $this->createAndFetchMocks(true);

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'activation_status'         => 'under_review',
            'activation_form_milestone' => 'L1'
        ]);

        $merchantId = $merchantDetail->getMerchantId();

        $this->fixtures->merchant->createAccount('100DemoAccount');

        $this->fixtures->on('live')->edit('merchant', $merchantId, [
            'parent_id' => '100DemoAccount'
        ]);

        $this->createTransaction($merchantId, 'payment', 5000, self::DATALAKE);
        $this->createTransaction($merchantId, 'payment', 5000);

        $this->mockDCS();

        (new Escalations\Core)->triggerPaymentEscalations();

        $escalation = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');

        // Verify no escalation is triggered for the merchant
        $this->assertEmpty($escalation);
    }

    public function testEscalation10kMilestoneTimeBoundTrueFilterNonRazorpayOrg()
    {
        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $this->createAndFetchMocks(true);

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'activation_status'         => 'under_review',
            'activation_form_milestone' => 'L1'
        ]);

        $merchantId = $merchantDetail->getMerchantId();

        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->on('live')->edit('merchant', $merchantId, [
            'org_id' => Org::HDFC_ORG
        ]);

        $this->createTransaction($merchantId, 'payment', 5000, self::DATALAKE);
        $this->createTransaction($merchantId, 'payment', 5000);

        $this->mockDCS();

        (new Escalations\Core)->triggerPaymentEscalations();

        $escalation = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');

        // Verify no escalation is triggered for the merchant
        $this->assertEmpty($escalation);
    }

    /**
     * Scenario:
     * -1 merchant is moved to activated mcc pending state
     * -2 merchant org is HDFC (Non Razorpay Org)
     * -3 merchant has accepted payment of worth 30k+200
     * -4 custom limit feature flag is enable ion HDFC org
     * -5 and a custom hard limit of 30k is assign using Mock
     * -6 Escalation should not be triggered by handleOnboardingEscalationsCron if timbound = true
     * -6 But Escalation should be triggered by handleBankingOrgOnboardingEscalationsCron as GMV is crossing hard limit
     * -7 asserting to check if right Escalation and Action triggered
     */
    public function testBankingOrgEscalationCustomTransactionLimitNonRazorpayOrg()
    {
        $limit = 30000;

        $this->app->instance("rzp.mode", Mode::LIVE);

        Mail::fake();

        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $this->createAndFetchMocks(true);

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('hard_limit');


        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->on('live')->edit('merchant', $merchantDetail->getMerchantId(), [
            'org_id' => Org::HDFC_ORG
        ]);

        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', $limit, self::DATALAKE);
        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 200);

        $this->fixtures->create('state', [
            State\Entity::ENTITY_ID     => $merchantDetail->getMerchantId(),
            State\Entity::NAME          => MerchantDetail\Status::ACTIVATED_MCC_PENDING,
            State\Entity::ENTITY_TYPE   => 'merchant_detail'
        ]);

        $this->mockDCS($limit*100,Org::HDFC_ORG,true);

        (new Escalations\Core)->triggerPaymentEscalations(true);

        $escalation = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');

        $this->assertEmpty($escalation);

        (new Escalations\Core)->handlePaymentEscalationsForBankingOrg();

        $merchant = $this->getDbEntityById('merchant', $merchantDetail->getMerchantId());

        $this->assertTrue($merchant->getAttribute(MerchantEntity::HOLD_FUNDS));

        $paymentEscalationMatrix = Escalations\Constants::BANKING_ORG_PAYMENTS_ESCALATION_MATRIX;

        if ($limit*100 != Escalations\Constants::DEFAULT_ESCALATION_TRANSACTION_LIMIT_FOR_KYC_PENDING and
            $limit*100 > Escalations\Constants::THRESHOLD_BEFORE_TRANSACTION_LIMIT_FOR_KYC_PENDING and
            $limit*100 < Escalations\Constants::THRESHOLD_AFTER_TRANSACTION_LIMIT_FOR_KYC_PENDING){

            $paymentEscalationMatrix[$limit*100] = $paymentEscalationMatrix[Escalations\Constants::DEFAULT_ESCALATION_TRANSACTION_LIMIT_FOR_KYC_PENDING];

            unset($paymentEscalationMatrix[Escalations\Constants::DEFAULT_ESCALATION_TRANSACTION_LIMIT_FOR_KYC_PENDING]);
        }

        $this->verifyEscalationAndAction('hard_limit_level_4', $limit * 100,false,$paymentEscalationMatrix);
    }

    /**
     * Scenario:
     * -1 merchant is moved to activated mcc pending state
     * -2 merchant org is HDFC (Non Razorpay Org)
     * -3 merchant has accepted payment of worth 50k+200
     * -3 custom limit feature flag is not enable on HDFC org, so
     * -6 No Escaltion should triggered by handleBankingOrgOnboardingEscalationsCron cron job
     * -7 but Escalation should triggered by handleOnboardingEscalationsCron cron if timbound = true
     */
    public function testBankingOrgEscalationDefaultTransactionLimitNonRazorpayOrg()
    {
        $limit = 50000;

        $this->app->instance("rzp.mode", Mode::LIVE);

        Mail::fake();

        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $this->createAndFetchMocks(true);

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('hard_limit');


        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->on('live')->edit('merchant', $merchantDetail->getMerchantId(), [
            'org_id' => Org::HDFC_ORG
        ]);

        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', $limit, self::DATALAKE);
        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 200);

        $this->fixtures->create('state', [
            State\Entity::ENTITY_ID     => $merchantDetail->getMerchantId(),
            State\Entity::NAME          => MerchantDetail\Status::ACTIVATED_MCC_PENDING,
            State\Entity::ENTITY_TYPE   => 'merchant_detail'
        ]);

        $this->mockDCS($limit*100);

        (new Escalations\Core)->handlePaymentEscalationsForBankingOrg();

        $escalation = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');

        $this->assertEmpty($escalation);

        (new Escalations\Core)->triggerPaymentEscalations(true);

        $merchant = $this->getDbEntityById('merchant', $merchantDetail->getMerchantId());

        $this->assertTrue($merchant->getAttribute(MerchantEntity::HOLD_FUNDS));

        $this->verifyEscalationAndAction('hard_limit_level_4', $limit * 100);

    }

    /**
     * Scenario:
     * call to Dcs for fetching org having this fearure failed
     */
    public function testBankingOrgEscalationDCSFetchFailedNonRazorpayOrg(){
        $limit = 30000;

        $this->app->instance("rzp.mode", Mode::LIVE);

        Mail::fake();

        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $this->createAndFetchMocks(true);

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('hard_limit');


        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->on('live')->edit('merchant', $merchantDetail->getMerchantId(), [
            'org_id' => Org::HDFC_ORG
        ]);

        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', $limit, self::DATALAKE);
        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 200);

        $this->fixtures->create('state', [
            State\Entity::ENTITY_ID     => $merchantDetail->getMerchantId(),
            State\Entity::NAME          => MerchantDetail\Status::ACTIVATED_MCC_PENDING,
            State\Entity::ENTITY_TYPE   => 'merchant_detail'
        ]);

        $this->mockDCSThrowException();

        try {
            (new Escalations\Core)->handlePaymentEscalationsForBankingOrg();
        }catch (\Exception $e) {
            $this->assertEquals('Mocked exception', $e->getMessage());
        }

        $escalation = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');

        $this->assertEmpty($escalation);
    }

    public function testHardLimitNoDocEscalationWithIncompleteKYC()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'on',
                ]
            ]
        ];

        $this->mockCareResponse();

        $this->mockSplitzTreatment($output);

        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $merchant = $this->createPrerequisiteForNoDocEscalation();

        $this->fixtures->on('live')->create('merchant_detail:filled_entity', [
            'merchant_id'       => $merchant['id'],
            'activation_status' => 'activated_kyc_pending',
            'business_website'  => 'http://hello.com'
        ]);

        $this->createTransaction($merchant->getId(), 'payment', 27800, self::DATALAKE);
        $this->createTransaction($merchant->getId(), 'payment', 27800);

        (new Escalations\Core())->handleNoDocGmvLimitBreach();

        $this->performAssertionsForNoDocTests($merchant, Escalations\Constants::HARD_LIMIT_NO_DOC,
            Escalations\Constants::HARD_LIMIT_KYC_PENDING_THRESHOLD_2_WAY, MerchantDetail\Status::NEEDS_CLARIFICATION);

        $this->assertFundHoldsForNoDoc($merchant, true, true,Actions\Handlers\Constants::HOLD_FUNDS_REASON_FOR_NO_DOC_LIMIT_BREACH);

        $this->assertFeatureAbsence('no_doc_onboarding', $merchant->id);
    }

    protected function mockSplitzTreatment($output)
    {
        $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->andReturn($output);
    }

    protected function mockSplitzTreatmentForMode($mode){
        $output = [
            "response" => [
                "variant" => [
                    "name" => $mode,
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);
    }

    protected function mockCareResponse()
    {
        $careMock = Mockery::mock(CareServiceClient::class);
        $this->app->instance('care_service', $careMock);

        $careMock->shouldReceive('internalPostRequest')
            ->andReturn(["success" => true]);
    }

    public function testEscalation10kMilestoneTimeBoundFalseFilterNoDocMerchant()
    {
        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $this->createAndFetchMocks(true);

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'activation_status'         => 'under_review',
            'activation_form_milestone' => 'L1'
        ]);

        $merchantId = $merchantDetail->getMerchantId();

        $this->fixtures->create('feature', [
            'name'        => 'no_doc_onboarding',
            'entity_id'   => $merchantId,
            'entity_type' => 'merchant'
        ]);

        $this->createTransaction($merchantId, 'payment', 5000, self::DATALAKE);
        $this->createTransaction($merchantId, 'payment', 5000);

        $this->mockDCS();

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $escalation = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');

        // Verify no escalation is triggered for the merchant
        $this->assertEmpty($escalation);
    }

    public function testHybridDataQueryingOffModeMerchantEscalations()
    {
        $this->app->instance("rzp.mode", Mode::LIVE);
        Mail::fake();

        $this->createAndFetchMocks(true);

        $this->mockSplitzTreatmentForMode(Escalations\Core::OFF);

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('L1');
        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 10000);

        $this->mockDCS();

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $this->verifyEscalationAndAction('L1', 1500000);
    }

    public function testHybridDataQueryingLiveModeMerchantEscalations()
    {
        $this->app->instance("rzp.mode", Mode::LIVE);
        Mail::fake();

        $this->createAndFetchMocks(true);

        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('L1');
        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 3000);
        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 9000, self::DATALAKE);

        $this->mockDCS();

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $this->verifyEscalationAndAction('L1', 1000000);
    }

    public function testHybridDataQueryingShadowModeMerchantEscalations()
    {
        $this->app->instance("rzp.mode", Mode::LIVE);
        Mail::fake();

        $this->createAndFetchMocks(true);

        $this->mockSplitzTreatmentForMode(Escalations\Core::SHADOW);

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('L1');
        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 4000);
        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 12000, self::DATALAKE);

        $this->mockDCS();

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $this->verifyEscalationAndAction('L1', 1500000);
    }

    public function testHardLimitNoDocEscalationInNeedsClarificationState()
    {
        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $merchant = $this->createPrerequisiteForNoDocEscalation();

        $this->fixtures->on('live')->create('merchant_detail:filled_entity', [
            'merchant_id'       => $merchant['id'],
            'activation_status' => 'needs_clarification',
            'business_website'  => 'http://hello.com'
        ]);

        $this->expectWebhookEvent('account.no_doc_onboarding_gmv_limit_warning',
            function (MerchantEntity $merchant, array $event)
            {
                $this->assertArraySubset([
                    'acc_id'         => $merchant->getId(),
                    'gmv_limit'      => 50000,
                    'current_gmv'    => 55600,
                    'message'        => 'You have breached the GMV limit. In order to remove this limit, kindly submit the KYC documents.',
                    'live'           => false,
                    'funds_on_hold'  => true
                ], $event['payload']);

            });

        $this->createTransaction($merchant->getId(), 'payment', 278000, self::DATALAKE);
        $this->createTransaction($merchant->getId(), 'payment', 278000);

        (new Escalations\Core())->handleNoDocGmvLimitBreach();

        $this->performAssertionsForNoDocTests($merchant, Escalations\Constants::HARD_LIMIT_NO_DOC,
            Escalations\Constants::HARD_LIMIT_KYC_PENDING_THRESHOLD_2_WAY, MerchantDetail\Status::NEEDS_CLARIFICATION);

        $this->assertFundHoldsForNoDoc($merchant, true, true,Actions\Handlers\Constants::HOLD_FUNDS_REASON_FOR_NO_DOC_LIMIT_BREACH);

        $this->assertFeatureAbsence('no_doc_onboarding', $merchant->id);
    }

    public function testHardLimitNoDocEscalationInUnderReviewState()
    {
        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $merchant = $this->createPrerequisiteForNoDocEscalation();

        $this->fixtures->on('live')->create('merchant_detail:filled_entity', [
            'merchant_id'       => $merchant['id'],
            'activation_status' => 'under_review',
            'business_website'  => 'http://hello.com'
        ]);

        $this->createTransaction($merchant->getId(), 'payment', 278000, self::DATALAKE);
        $this->createTransaction($merchant->getId(), 'payment', 278000);

        (new Escalations\Core())->handleNoDocGmvLimitBreach();

        $this->performAssertionsForNoDocTests($merchant, Escalations\Constants::HARD_LIMIT_NO_DOC,
            Escalations\Constants::HARD_LIMIT_KYC_PENDING_THRESHOLD_2_WAY, MerchantDetail\Status::UNDER_REVIEW);

        $this->assertFundHoldsForNoDoc($merchant, true, true,Actions\Handlers\Constants::HOLD_FUNDS_REASON_FOR_NO_DOC_LIMIT_BREACH);

        $this->assertFeatureAbsence('no_doc_onboarding', $merchant->id);
    }

    public function testHardLimitNoDocEscalationWithCompleteKYC()
    {
        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $merchant = $this->createPrerequisiteForNoDocEscalation();

        //submitting docs during this call( with createValidFields). Also marking gst status as verified to check 3-way flow
        $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'merchant_id'                   => $merchant['id'],
            'activation_status'             => 'activated_kyc_pending',
            'business_website'              => 'http://hello.com',
            'gstin_verification_status'     => 'verified'
        ]);

        $this->fixtures->on('live')->create('file_store', [
            'id'            => 'abcdef12345678',
            'merchant_id'   => $merchant['id']
        ]);

        $this->createTransaction($merchant->getId(), 'payment', 278000, self::DATALAKE);
        $this->createTransaction($merchant->getId(), 'payment', 278000);

        (new Escalations\Core())->handleNoDocGmvLimitBreach();

        $this->performAssertionsForNoDocTests($merchant, Escalations\Constants::HARD_LIMIT_NO_DOC,
            Escalations\Constants::HARD_LIMIT_KYC_PENDING_THRESHOLD_3_WAY, MerchantDetail\Status::UNDER_REVIEW);

        $this->assertFundHoldsForNoDoc($merchant, true, true, Actions\Handlers\Constants::HOLD_FUNDS_REASON_FOR_NO_DOC_LIMIT_BREACH);

        $this->assertFeatureAbsence('no_doc_onboarding', $merchant->id);
    }

    public function testNoDocEscalationWithPartnerConfigGmvValue()
    {
        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $merchant = $this->createPrerequisiteForNoDocEscalation();

        //submitting docs during this call( with createValidFields). Also marking gst status as verified to check 3-way flow
        $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'merchant_id'                   => $merchant['id'],
            'activation_status'             => 'activated_kyc_pending',
            'business_website'              => 'http://hello.com',
            'gstin_verification_status'     => 'verified'
        ]);

        $randomApplicationId = str_random(14);

        $this->fixtures->on('live')->create('file_store', [
            'id'            => 'abcdef12345678',
            'merchant_id'   => $merchant['id']
        ]);

        $this->fixtures->create('merchant_access_map', [
            'entity_owner_id' => '10000000000000',
            'merchant_id'     => $merchant->getId(),
            'entity_type'     => 'application',
            'entity_id'       => $randomApplicationId
        ]);

        $this->fixtures->create('merchant_application', [
            'merchant_id'     => '10000000000000',
            'type'            => 'managed',
            'application_id'  => $randomApplicationId
        ]);

        $this->fixtures->create("partner_config", [
            'entity_id' => $randomApplicationId,
            'entity_type' => 'application',
            'sub_merchant_config' => json_decode('{"gmv_limit":[{"value":10100000,"set_for":"no_doc_submerchants"}]}', 1)
        ]);

        $this->expectWebhookEvent('account.no_doc_onboarding_gmv_limit_warning',
            function (MerchantEntity $merchant, array $event)
            {
                $this->assertArraySubset([
                    'acc_id'         => $merchant->getId(),
                    'gmv_limit'      => 10100000,
                    'current_gmv'    => 10110000,
                    'message'        => 'You have breached the GMV limit. You can continue to accept payments after full account activation.',
                    'live'           => false,
                    'funds_on_hold'  => true
                ], $event['payload']);

            });

        $this->createTransaction($merchant->getId(), 'payment', 5055000, self::DATALAKE);
        $this->createTransaction($merchant->getId(), 'payment', 5055000);

        (new Escalations\Core())->handleNoDocGmvLimitBreach();

        $this->performAssertionsForNoDocTests($merchant, Escalations\Constants::HARD_LIMIT_NO_DOC,
            10100000, MerchantDetail\Status::UNDER_REVIEW);

        $this->assertFundHoldsForNoDoc($merchant, true, true, Actions\Handlers\Constants::HOLD_FUNDS_REASON_FOR_NO_DOC_LIMIT_BREACH);

        $this->assertFeatureAbsence('no_doc_onboarding', $merchant->id);
    }

    public function testNinetyPercentileGmvWarningForNoDoc()
    {
        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $merchant = $this->createPrerequisiteForNoDocEscalation();

        $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'merchant_id'       => $merchant['id'],
            'activation_status' => 'activated_kyc_pending',
            'business_website'  => 'http://hello.com'
        ]);

        $this->expectWebhookEvent('account.no_doc_onboarding_gmv_limit_warning',
            function (MerchantEntity $merchant, array $event)
            {
                $this->assertArraySubset([
                    'acc_id'         => $merchant->getId(),
                    'gmv_limit'      => 50000,
                    'current_gmv'    => 45000,
                    'message'        => 'You can accept payments upto INR 5000. In order to remove this limit, kindly submit the KYC documents.',
                    'live'           => true,
                    'funds_on_hold'  => false
                ], $event['payload']);

            });

        $this->createTransaction($merchant->getId(), 'payment', 22500, self::DATALAKE);
        $this->createTransaction($merchant->getId(), 'payment', 22500);

        (new Escalations\Core())->handleNoDocGmvLimitBreach();

        $this->performAssertionsForNoDocTests($merchant, Escalations\Constants::NO_DOC_P90_GMV,
            Escalations\Constants::HARD_LIMIT_KYC_PENDING_THRESHOLD_2_WAY,
            MerchantDetail\Status::ACTIVATED_KYC_PENDING);

        $this->assertFundHoldsForNoDoc($merchant, false, false);
    }

    public function testNinetyPercentileGmvWarningForNoDocInUnderReview()
    {
        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $merchant = $this->createPrerequisiteForNoDocEscalation();

        $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'merchant_id'       => $merchant['id'],
            'activation_status' => 'under_review',
            'business_website'  => 'http://hello.com'
        ]);

        $this->expectWebhookEvent('account.no_doc_onboarding_gmv_limit_warning',
            function (MerchantEntity $merchant, array $event)
            {
                $this->assertArraySubset([
                    'acc_id'         => $merchant->getId(),
                    'gmv_limit'      => 50000,
                    'current_gmv'    => 45000,
                    'message'        => 'You can accept payments upto INR 5000. You can continue to accept payments without any limits post full account activation.',
                    'live'           => true,
                    'funds_on_hold'  => false
                ], $event['payload']);

            });

        $this->createTransaction($merchant->getId(), 'payment', 22500, self::DATALAKE);
        $this->createTransaction($merchant->getId(), 'payment', 22500);

        (new Escalations\Core())->handleNoDocGmvLimitBreach();

        $this->performAssertionsForNoDocTests($merchant, Escalations\Constants::NO_DOC_P90_GMV,
            Escalations\Constants::HARD_LIMIT_KYC_PENDING_THRESHOLD_2_WAY,
            MerchantDetail\Status::UNDER_REVIEW);

        $this->assertFundHoldsForNoDoc($merchant, false, false);
    }

    public function testNinetyPercentileGmvWarningForNoDocInNC()
    {
        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $merchant = $this->createPrerequisiteForNoDocEscalation();

        $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'merchant_id'       => $merchant['id'],
            'activation_status' => 'needs_clarification',
            'business_website'  => 'http://hello.com'
        ]);

        $this->expectWebhookEvent('account.no_doc_onboarding_gmv_limit_warning',
            function (MerchantEntity $merchant, array $event)
            {
                $this->assertArraySubset([
                    'acc_id'         => $merchant->getId(),
                    'gmv_limit'      => 50000,
                    'current_gmv'    => 45000,
                    'message'        => 'You can accept payments upto INR 5000. In order to remove this limit, kindly provide responses to outstanding clarifications for submitted KYC documents.',
                    'live'           => true,
                    'funds_on_hold'  => false
                ], $event['payload']);

            });

        $this->createTransaction($merchant->getId(), 'payment', 22500, self::DATALAKE);
        $this->createTransaction($merchant->getId(), 'payment', 22500);

        (new Escalations\Core())->handleNoDocGmvLimitBreach();

        $this->performAssertionsForNoDocTests($merchant, Escalations\Constants::NO_DOC_P90_GMV,
            Escalations\Constants::HARD_LIMIT_KYC_PENDING_THRESHOLD_2_WAY,
            MerchantDetail\Status::NEEDS_CLARIFICATION);

        $this->assertFundHoldsForNoDoc($merchant, false, false);
    }

    public function testNinetyOnePercentileGmvWarningForNoDoc()
    {
        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $merchant = $this->createPrerequisiteForNoDocEscalation();

        $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'merchant_id'       => $merchant['id'],
            'activation_status' => 'activated_kyc_pending',
            'business_website'  => 'http://hello.com'
        ]);

        $this->expectWebhookEvent('account.no_doc_onboarding_gmv_limit_warning',
            function (MerchantEntity $merchant, array $event)
            {
                $this->assertArraySubset([
                    'acc_id'         => $merchant->getId(),
                    'gmv_limit'      => 50000,
                    'current_gmv'    => 47000,
                    'message'        => 'You can accept payments upto INR 3000. In order to remove this limit, kindly submit the KYC documents.',
                    'live'           => true,
                    'funds_on_hold'  => false
                ], $event['payload']);

            });

        $this->createTransaction($merchant->getId(), 'payment', 23500, self::DATALAKE);
        $this->createTransaction($merchant->getId(), 'payment', 23500);

        (new Escalations\Core())->handleNoDocGmvLimitBreach();

        $this->performAssertionsForNoDocTests($merchant, Escalations\Constants::NO_DOC_P91_GMV,
            Escalations\Constants::HARD_LIMIT_KYC_PENDING_THRESHOLD_2_WAY,
            MerchantDetail\Status::ACTIVATED_KYC_PENDING);

        $this->assertFundHoldsForNoDoc($merchant, false, false);
    }

    public function testNoDocEscalationWithGmvLessThanNinetyPercentOfThreshold()
    {
        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $merchant = $this->createPrerequisiteForNoDocEscalation();

        $this->createAndFetchMocks(true);

        $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'merchant_id'       => $merchant['id'],
            'activation_status' => 'activated_kyc_pending',
            'business_website'  => 'http://hello.com'
        ]);

        $this->createTransaction($merchant->getId(), 'payment', 20000, self::DATALAKE);
        $this->createTransaction($merchant->getId(), 'payment', 20000);

        (new Escalations\Core())->handleNoDocGmvLimitBreach();

        $escalationV2 = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');
        self::assertEmpty($escalationV2);

        $merchant = $this->getDbEntityById('merchant', $merchant->id);
        self::assertEquals(false, $merchant->getAttribute('hold_funds'));

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchant->id);
        self::assertEquals(MerchantDetail\Status::ACTIVATED_KYC_PENDING, $merchantDetail->getAttribute('activation_status'));
    }

    private function performAssertionsForNoDocTests(MerchantEntity $merchant, string $expEscalationMilestone,
                                                    string $expEscalationThreshold, string $expectedActivationStatus)
    {
        $escalationV2 = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');
        self::assertNotEmpty($escalationV2);
        self::assertEquals('merchant', $escalationV2->getAttribute('escalated_to'));
        self::assertEquals($expEscalationMilestone, $escalationV2->getAttribute('milestone'));
        self::assertEquals($expEscalationThreshold, $escalationV2->getAttribute('threshold'));

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchant->id);
        self::assertEquals($expectedActivationStatus, $merchantDetail->getAttribute('activation_status'));
    }

    private function assertFundHoldsForNoDoc(MerchantEntity $merchant, bool $expHoldFunds, bool $expStopPayments, string $expHoldFundsReason = null)
    {
        $merchant = $this->getDbEntityById('merchant', $merchant->getId());

        self::assertEquals($expStopPayments, !$merchant->getAttribute('live'));
        self::assertEquals($expHoldFunds, $merchant->getAttribute('hold_funds'));
        self::assertEquals($expHoldFundsReason, $merchant->getAttribute('hold_funds_reason'));
    }

    private function assertFeatureAbsence(string $featureName, string $merchantId)
    {
        $feature = $this->getDbEntity(
            'feature',
            [
                'entity_id'   => $merchantId,
                'entity_type' => 'merchant',
                'name'        => $featureName
            ]);

        $this->assertNull($feature);
    }

    private function createPrerequisiteForNoDocEscalation()
    {
        $this->createAndFetchMocks(true);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId('100000razorpay');

        $merchant = $this->fixtures->on('live')->create('merchant', [
            'live'       => true,
            'activated'  => 1,
            'hold_funds' => false
        ]);

        $this->fixtures->create('feature', [
            'name'        => 'no_doc_onboarding',
            'entity_id'   => $merchant->id,
            'entity_type' => 'merchant'
        ]);
        return $merchant;
    }

    public function testInstantActivationSoftLimit_5k_EscalationV2()
    {
        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $this->createAndFetchMocks(true);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId('100000razorpay');

        $escalationCoreMock = $this->getMockBuilder(Escalations\Core::class)
            ->setMethods(['canTriggerIAWebhookEscalation'])
            ->getMock();

        $escalationCoreMock->expects($this->any())
            ->method('canTriggerIAWebhookEscalation')
            ->willReturn(true);

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('L1');

        $merchantId = $merchantDetail->getId();

        $this->createTransaction($merchantId, 'payment', 2525, self::DATALAKE);
        $this->createTransaction($merchantId, 'payment', 2525);

        $this->mockDCS();

        $escalationCoreMock->triggerPaymentEscalations(false);

        $escalations = $this->getDbEntities('merchant_onboarding_escalations',[],'live');

        $threshold = 500000;

        $this->verifyEscalationAndActionInstAct_V2('L1', $threshold,
            $escalations[0], Escalations\Constants::PAYMENTS_ESCALATION_MATRIX[$threshold]);

        $this->verifyEscalationAndActionInstAct_V2('soft_limit_ia_v2', $threshold,
            $escalations[1], Escalations\Constants::INSTANT_ACTIVATION_V2_API_ESCALATION_MATRIX[$threshold]);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        self::assertEquals(true, $merchant->getAttribute('live'));
    }

    public function testInstantActivationSoftLimitV2Escalation_10k_milestone()
    {
        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $this->createAndFetchMocks(true);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId('100000razorpay');

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('L1');

        $merchantId = $merchantDetail->getId();

        $escalationCoreMock = $this->getMockBuilder(Escalations\Core::class)
            ->setMethods(['canTriggerIAWebhookEscalation'])
            ->getMock();

        $escalationCoreMock->expects($this->any())
            ->method('canTriggerIAWebhookEscalation')
            ->willReturn(true);

        $this->createTransaction($merchantId, 'payment', 5000, self::DATALAKE);
        $this->createTransaction($merchantId, 'payment', 5000);

        $this->addEscalation('L1', 500000);
        $this->addEscalation('soft_limit_ia_v2',500000);

        $this->mockDCS();

        $escalationCoreMock->triggerPaymentEscalations(false);

        $escalations = $this->getDbEntities('merchant_onboarding_escalations',[],'live');

        $threshold = 1000000;

        $this->verifyEscalationAndActionInstAct_V2('L1', $threshold,
            $escalations[2], Escalations\Constants::PAYMENTS_ESCALATION_MATRIX[$threshold]);

        $this->verifyEscalationAndActionInstAct_V2('soft_limit_ia_v2', $threshold,
            $escalations[3], Escalations\Constants::INSTANT_ACTIVATION_V2_API_ESCALATION_MATRIX[$threshold]);
    }

    public function testInstantActivationSoftLimitV2Escalation_15kLimitBreached()
    {
        $this->mockSplitzTreatmentForMode(Escalations\Core::LIVE);

        $this->createAndFetchMocks(true);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId('100000razorpay');

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('L1');

        $merchantId = $merchantDetail->getId();

        $escalationCoreMock = $this->getMockBuilder(Escalations\Core::class)
            ->setMethods(['canTriggerIAWebhookEscalation'])
            ->getMock();

        $escalationCoreMock->expects($this->any())
            ->method('canTriggerIAWebhookEscalation')
            ->willReturn(true);

        $this->createTransaction($merchantId, 'payment', 10000, self::DATALAKE);
        $this->createTransaction($merchantId, 'payment', 5200);

        $this->addEscalation('L1', 500000);
        $this->addEscalation('soft_limit_ia_v2',500000);
        $this->addEscalation('L1', 1000000);
        $this->addEscalation('soft_limit_ia_v2',1000000);

        $this->mockDCS();

        $escalationCoreMock->triggerPaymentEscalations(false);

        $escalations = $this->getDbEntities('merchant_onboarding_escalations',[],'live');

        $threshold = 1500000;

        $this->verifyEscalationAndActionInstAct_V2('L1', $threshold,
            $escalations[4], Escalations\Constants::PAYMENTS_ESCALATION_MATRIX[$threshold]);

        $this->verifyEscalationAndActionInstAct_V2('hard_limit_ia_v2', $threshold,
            $escalations[5], Escalations\Constants::INSTANT_ACTIVATION_V2_API_ESCALATION_MATRIX[$threshold]);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        self::assertEquals(false, $merchant->getAttribute('live'));
    }

    private function createAndFetchMocks($razorXEnabled)
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app['razorx']->method('getTreatment')
            ->willReturn($razorXEnabled ? 'on' : 'off');
    }

    private function addEscalation($milestone, $threshold)
    {
        $escalation = $this->fixtures->on('live')->create('merchant_onboarding_escalations', [
            'milestone' => $milestone,
            'threshold' => $threshold
        ]);

        return $escalation;
    }

    private function createAndFetchFixturesForMilestone($milestone)
    {
        $merchantAttributes       = [];
        $merchantDetailAttributes = [];

        switch ($milestone)
        {
            case 'L1':
                $merchantAttributes       = [
                    'activated' => 1,
                    'live'      => true
                ];
                $merchantDetailAttributes = [
                    'activation_status'         => 'instantly_activated',
                    'activation_form_milestone' => 'L1'
                ];
                break;
            case 'L2':
                $merchantAttributes       = [
                    'activated' => 1,
                    'live'      => true
                ];
                $merchantDetailAttributes = [
                    'activation_status'         => 'under_review',
                    'activation_form_milestone' => 'L2'
                ];
                break;
            case 'soft_limit':
            case 'hard_limit':
                $merchantAttributes       = [
                    'activated' => 1,
                    'live'      => true
                ];
                $merchantDetailAttributes = [
                    'activation_status'         => 'activated_mcc_pending',
                    'activation_form_milestone' => 'L2',
                    'submitted'                 => 1
                ];
                break;
        }
        $merchant   = $this->fixtures->create('merchant', $merchantAttributes);
        $merchantId = $merchant->getId();

        $merchantDetailAttributes = array_merge($merchantDetailAttributes, ['merchant_id' => $merchant->getId()]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $merchantDetailAttributes);

        return [$merchantDetail];
    }

    private function createTransaction(string $merchantId, string $type, int $amount, string $dataDestination = self::PINOT)
    {
        $transaction = $this->fixtures->on('live')->create('transaction', [
            'type'        => $type,
            'amount'      => $amount * 100,   // in paisa
            'merchant_id' => $merchantId
        ]);

        if ($dataDestination === self::PINOT)
        {
            $this->mockPinot($merchantId, $amount);
        }
        if ($dataDestination === self::DATALAKE)
        {
            $this->mockDataLake($merchantId, $amount);
        }
    }

    private function mockPinot(string $merchantId, int $amount)
    {
        $pinotService = $this->getMockBuilder(HarvesterClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getDataFromPinot'])
            ->getMock();

        $this->app->instance('eventManager', $pinotService);

        $dataFromPinot = ['merchant_id' => $merchantId, "amount" => $amount * 100, "transacted_merchants_count" => 1];
        $cumulativeDataFromPinot = ['merchant_id' => $merchantId, "amount" => 4*$amount*100, "transacted_merchants_count" => 1];

        $pinotService->method('getDataFromPinot')
            ->will(
                $this->returnCallback(function ($content) use($dataFromPinot, $cumulativeDataFromPinot)
                {
                    $query = $content['query'];

                    // If $query string doesn't contain created_at filter, it is querying for cumulative data
                    // Hence, return cumulativeDataFromPinot, else return dataFromPinot
                    if (strpos($query, 'created_at') === false)
                    {
                        return [$cumulativeDataFromPinot];
                    }
                    else
                    {
                        return [$dataFromPinot];
                    }
                })
        );
    }

    private function mockDataLake(string $merchantId, int $amount)
    {
        $prestoService = $this->getMockBuilder(DataLakePresto::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getDataFromDataLake'])
            ->getMock();

        $this->app->instance('datalake.presto', $prestoService);

        $dataFromDataLake = ['merchant_id' => $merchantId, "amount" => $amount * 100, "transacted_merchants_count" => 1];

        $prestoService->method('getDataFromDataLake')
            ->willReturn([$dataFromDataLake]);
    }

    private function verifyEscalationAndAction($milestone, $threshold, $emptyAction = false, $paymentsEscalationConfig = Escalations\Constants::PAYMENTS_ESCALATION_MATRIX)
    {
        $expectedEscalationConfig = null;
        foreach ( $paymentsEscalationConfig[$threshold] as $config)
        {
            if ($config[Escalations\Constants::MILESTONE] === $milestone)
            {
                $expectedEscalationConfig = $config;
                break;
            }
        }

        $escalation = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');

        // Verify that escalation is created in db
        $this->assertNotEmpty($escalation);
        $this->assertEquals($milestone, $escalation->getAttribute('milestone'));
        $this->assertEquals($threshold, $escalation->getAttribute('threshold'));

        $actions = DB::table('onboarding_escalation_actions')
            ->where('escalation_id', $escalation->getId())
            ->get()->toArray();

        if ($emptyAction === true)
        {
            self::assertEmpty($actions);

            return;
        }

        self::assertNotEmpty($actions);

        foreach ($actions as $action)
        {
            $this->assertEquals($escalation->getAttribute('id'), $action->escalation_id);

            $expected = Actions\Constants::SUCCESS . '|' . $action->action_handler;
            $actual   = $action->status . '|' . $action->action_handler;
            $this->assertEquals($expected, $actual);

            $actionConfig = $this->getActionconfig($action->action_handler, $expectedEscalationConfig);

            self::assertNotEmpty($actionConfig);

            $this->verifyAction($action, $actionConfig);
        }
    }

    private function getActionconfig($handler, $expectedEscalationConfig)
    {
        foreach ($expectedEscalationConfig['actions'] as $actionConfig)
        {
            $handlerClazz = Escalations\Utils::getClassShortName($actionConfig['handler']);
            if ($handler === $handlerClazz)
            {
                return $actionConfig;
            }
        }

        return null;
    }

    private function verifyAction($action, array $actionConfig)
    {
        $params = $actionConfig[Escalations\Constants::PARAMS] ?? [];

        switch ($action->action_handler)
        {
            case Actions\Handlers\CommunicationHandler::class:
                $event = $params['event'];

                $emailTemplate = Events::EMAIL_TEMPLATES[$event];

                //verify email has been sent
                Mail::assertQueued(MerchantOnboardingEmail::class, function($mail) use ($emailTemplate) {
                    $viewData = $mail->viewData;

                    $this->assertEquals($emailTemplate, $mail->view);

                    return true;
                });
                break;
            case Actions\Handlers\DisablePaymentsHandler::class:
                $merchant = $this->getDbLastEntity('merchant');

                $this->assertFalse($merchant->getAttribute('live'));
                $this->assertEquals(0, $merchant->getAttribute('activated'));
                break;
        }
    }

    private function verifyEscalationAndActionInstAct_V2($milestone, $threshold, $escalation, $escalationMatrix, $emptyAction = false)
    {
        $expectedEscalationConfig = null;
        foreach ($escalationMatrix as $config)
        {
            if ($config[Escalations\Constants::MILESTONE] === $milestone)
            {
                $expectedEscalationConfig = $config;
                break;
            }
        }

        // Verify that escalation is created in db
        $this->assertNotEmpty($escalation);
        $this->assertEquals($milestone, $escalation->getAttribute('milestone'));
        $this->assertEquals($threshold, $escalation->getAttribute('threshold'));

        $this->verifyInstantActivationV2Action($escalation, $expectedEscalationConfig);
    }

    private function verifyInstantActivationV2Action($escalation, $expectedEscalationConfig)
    {
        $actions = DB::table('onboarding_escalation_actions')
            ->where('escalation_id', $escalation->getId())
            ->get()->toArray();

        self::assertNotEmpty($actions);

        foreach ($actions as $action)
        {
            $this->assertEquals($escalation->getAttribute('id'), $action->escalation_id);

            $expected = Actions\Constants::SUCCESS . '|' . $action->action_handler;
            $actual   = $action->status . '|' . $action->action_handler;
            $this->assertEquals($expected, $actual);

            $actionConfig = $this->getActionconfig($action->action_handler, $expectedEscalationConfig);

            self::assertNotEmpty($actionConfig);

            $this->verifyAction($action, $actionConfig);
        }
    }
}
