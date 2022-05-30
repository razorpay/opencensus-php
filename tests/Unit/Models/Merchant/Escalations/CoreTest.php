<?php


namespace Unit\Models\Merchant\Escalations;

use DB;
use Mail;
use Queue;
use RZP\Constants\Mode;
use RZP\Services\Mock\ApachePinotClient;
use RZP\Mail\Merchant\MerchantOnboardingEmail;
use RZP\Notifications\Onboarding\Events;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Escalations;
use RZP\Models\Merchant\Escalations\Actions;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Services\Mock\DruidService as MockDruidService;

class CoreTest extends TestCase
{
    use DbEntityFetchTrait;

    public function testNoEscalationTriggeredIfMerchantNotInOpenState()
    {
        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'activation_status' => 'activated'
        ]);
        $merchantId     = $merchantDetail->getMerchantId();

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $escalation = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');

        // Verify no escalation is triggered for the merchant
        $this->assertEmpty($escalation);
    }

    public function testNoEscalationTriggeredIfMerchantPaymentIsBelowThreshold()
    {
        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'activation_status' => 'under_review'
        ]);
        $merchantId     = $merchantDetail->getMerchantId();

        $this->createTransaction($merchantId, 'payment', 900);

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $escalation = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');

        // Verify no escalation is triggered for the merchant
        $this->assertEmpty($escalation);
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
        $this->createAndFetchMocks(true);

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('soft_limit');
        $merchantId = $merchantDetail->getMerchantId();

        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 1000);

        $existingEscalation = $this->addEscalation('soft_limit', 100000);

        [$triggered, $reason] = (new Escalations\Handler)->triggerPaymentEscalation(
            $merchantId, 100000, [$existingEscalation->toArray()]);

        $this->assertFalse($triggered);
    }

    public function testEscalation_5K_milestone_L1()
    {
        $this->app->instance("rzp.mode", Mode::LIVE);
        Mail::fake();

        $this->createAndFetchMocks(true);

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('L1');

        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 5000);

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $escalation = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');

        $this->verifyEscalationAndAction('L1', 500000);
    }

    public function testEscalation_10K_milestone_L1()
    {
        $this->app->instance("rzp.mode", Mode::LIVE);
        Mail::fake();

        $this->createAndFetchMocks(true);

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('L1');

        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 10000);

        $this->addEscalation('L1', 500000);

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $this->verifyEscalationAndAction('L1', 1000000);
    }

    public function testEscalation_15K_milestone_L1()
    {
        $this->app->instance("rzp.mode", Mode::LIVE);
        Mail::fake();

        $this->createAndFetchMocks(true);

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('L1');

        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 10000);
        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 5000);

        $this->addEscalation('L1', 500000);
        $this->addEscalation('L1', 1000000);

        $this->mockApachePinot($merchantDetail->getMerchantId(), 15000);

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $this->verifyEscalationAndAction('L1', 1500000);
    }

    public function testEscalation_1lakh_FOH()
    {
        $this->app->instance("rzp.mode", Mode::LIVE);

        Mail::fake();

        $this->createAndFetchMocks(true);

        [$merchantDetail] = $this->createAndFetchFixturesForMilestone('hard_limit');

        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 100000);
        $this->createTransaction($merchantDetail->getMerchantId(), 'payment', 200);

        $this->mockApachePinot($merchantDetail->getMerchantId(), 100200);

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $merchant = $this->getDbEntityById('merchant', $merchantDetail->getMerchantId());

        $this->assertTrue($merchant->getAttribute(MerchantEntity::HOLD_FUNDS));

        $this->verifyEscalationAndAction('hard_limit_level_4', 10000000);
    }

    public function testEscalation10kMilestoneTimeBoundFalseFilterLinkedAccount()
    {
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

        $this->createTransaction($merchantId, 'payment', 10000);

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $escalation = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');

        // Verify no escalation is triggered for the merchant
        $this->assertEmpty($escalation);
    }

    public function testEscalation10kMilestoneTimeBoundFalseFilterNonRazorpayOrg()
    {
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

        $this->createTransaction($merchantId, 'payment', 10000);

        (new Escalations\Core)->triggerPaymentEscalations(false);

        $escalation = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');

        // Verify no escalation is triggered for the merchant
        $this->assertEmpty($escalation);
    }

    public function testEscalation10kMilestoneTimeBoundTrueFilterLinkedAccount()
    {
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

        $this->createTransaction($merchantId, 'payment', 10000);

        (new Escalations\Core)->triggerPaymentEscalations(true);

        $escalation = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');

        // Verify no escalation is triggered for the merchant
        $this->assertEmpty($escalation);
    }

    public function testEscalation10kMilestoneTimeBoundTrueFilterNonRazorpayOrg()
    {
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

        $this->createTransaction($merchantId, 'payment', 10000);

        (new Escalations\Core)->triggerPaymentEscalations(true);

        $escalation = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');

        // Verify no escalation is triggered for the merchant
        $this->assertEmpty($escalation);
    }

    public function testHardLimitNoDocEscalation()
    {
        $this->createAndFetchMocks(true);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId('100000razorpay');

        $merchant = $this->fixtures->on('live')->create('merchant', [
            'live'       => true,
            'activated'  => 1,
            'hold_funds' => false
        ]);

        $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'merchant_id'       => $merchant['id'],
            'activation_status' => 'activated_kyc_pending',
            'business_website'  => 'http://hello.com'
        ]);

        $this->fixtures->create('feature', [
            'name'        => 'no_doc_onboarding',
            'entity_id'   => $merchant->id,
            'entity_type' => 'merchant'
        ]);

        $this->fixtures->create('merchant_product', [
            'merchant_id'       => $merchant->id,
            'product_name'      => 'payment_gateway',
            'activation_status' => 'activated'
        ]);

        $this->createTransaction($merchant->getId(), 'payment', 55000);

        (new Escalations\Core())->handleNoDocLimitBreach();

        $escalationV2 = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');
        self::assertNotEmpty($escalationV2);
        self::assertEquals('merchant', $escalationV2->getAttribute('escalated_to'));
        self::assertEquals('hard_limit_no_doc', $escalationV2->getAttribute('milestone'));
        self::assertEquals(Escalations\Constants::HARD_LIMIT_KYC_PENDING_THRESHOLD, $escalationV2->getAttribute('threshold'));

        $merchant = $this->getDbEntityById('merchant', $merchant->id);
        self::assertEquals(true, $merchant->getAttribute('hold_funds'));
        self::assertEquals('GMV hard limit for no-doc onboarding breached for the merchant.', $merchant->getAttribute('hold_funds_reason'));

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchant->id);
        self::assertEquals('needs_clarification', $merchantDetail->getAttribute('activation_status'));
    }

    public function testHardLimitNoDocEscalationWithGmvLessThanThreshold()
    {
        $this->createAndFetchMocks(true);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId('100000razorpay');

        $merchant = $this->fixtures->on('live')->create('merchant', [
            'live'       => true,
            'activated'  => 1,
            'hold_funds' => false
        ]);

        $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'merchant_id'       => $merchant['id'],
            'activation_status' => 'activated_kyc_pending',
            'business_website'  => 'http://hello.com'
        ]);

        $this->fixtures->create('feature', [
            'name'        => 'no_doc_onboarding',
            'entity_id'   => $merchant->id,
            'entity_type' => 'merchant'
        ]);

        $this->createTransaction($merchant->getId(), 'payment', 45000);

        (new Escalations\Core())->handleNoDocLimitBreach();

        $escalationV2 = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');
        self::assertEmpty($escalationV2);

        $merchant = $this->getDbEntityById('merchant', $merchant->id);
        self::assertEquals(false, $merchant->getAttribute('hold_funds'));

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchant->id);
        self::assertEquals('activated_kyc_pending', $merchantDetail->getAttribute('activation_status'));
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

    private function createTransaction(string $merchantId, string $type, int $amount)
    {
        $transaction = $this->fixtures->on('live')->create('transaction', [
            'type'        => $type,
            'amount'      => $amount * 100,   // in paisa
            'merchant_id' => $merchantId
        ]);

        $this->mockApachePinot($merchantId, $amount);
    }

    private function mockApachePinot(string $merchantId, int $amount)
    {
        $pinotService = $this->getMockBuilder(ApachePinotClient::class)
                             ->setConstructorArgs([$this->app])
                             ->onlyMethods(['getDataFromPinot'])
                             ->getMock();

        $this->app->instance('apache.pinot', $pinotService);

        $dataFromPinot = ['merchant_id' => $merchantId, "amount" => $amount * 100];

        $pinotService->method('getDataFromPinot')
                     ->willReturn([$dataFromPinot]);
    }

    private function verifyEscalationAndAction($milestone, $threshold, $emptyAction = false)
    {
        $expectedEscalationConfig = null;
        foreach (Escalations\Constants::PAYMENTS_ESCALATION_MATRIX[$threshold] as $config)
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
}
