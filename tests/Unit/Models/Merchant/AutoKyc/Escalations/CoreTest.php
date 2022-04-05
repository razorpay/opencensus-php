<?php


namespace Unit\Models\Merchant\AutoKyc\Escalations;

use DB;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Services\RazorXClient;
use RZP\Models\Admin\Permission;
use Illuminate\Support\Facades\Mail;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Mail\Merchant\MerchantOnboardingEmail;
use RZP\Models\Merchant\AutoKyc\Escalations\Core as EscalationCore;
use RZP\Models\Merchant\AutoKyc\Escalations\Constants as EscalationConstant;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;

class CoreTest extends TestCase
{
    use DbEntityFetchTrait;
    protected function createAndFetchMocks()
    {
        Mail::fake();
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                               ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function($mid, $feature, $mode) {
                                  if ($feature === RazorxTreatment::INSTANT_ACTIVATION_FUNCTIONALITY)
                                  {
                                      return 'on';
                                  }

                                  return 'off';
                              }));
    }
    private function createTransaction(string $merchantId, string $type, int $amount)
    {
        $transaction = $this->fixtures->on('live')->create('transaction', [
            'type'        => $type,
            'amount'      => $amount * 100,   // in paisa
            'merchant_id' => $merchantId
        ]);
    }

    private function createAndFetchFixtures()
    {
        $permission = $this->fixtures->connection('live')->create('permission', [
            'name' => Permission\Name::AUTO_KYC_SOFT_LIMIT_BREACH_UNREGISTERED
        ]);

        // Creating workflow
        $workflow = $this->fixtures->connection('live')->create('workflow', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
            'name'   => "NC Workflow"
        ]);

        // Attaching create_payout permission to the workflow
        DB::connection('live')->table('workflow_permissions')->insert([
                                                                          'workflow_id'   => $workflow->getId(),
                                                                          'permission_id' => $permission->getId()
                                                                      ]);
        DB::connection('live')->table('permission_map')->insert([
                                                                    'entity_id'     => OrgEntity::RAZORPAY_ORG_ID,
                                                                    'entity_type'   => 'org',
                                                                    'permission_id' => $permission->getId(),
                                                                ]);

        $merchant = $this->fixtures->on('live')->create('merchant', [
            'live'       => true,
            'activated'  => 1,
            'hold_funds' => false
        ]);

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'merchant_id'       => $merchant['id'],
            'activation_status' => 'activated_mcc_pending',
            'business_type'     => 11,
            'business_website'  => "http://hello.com"
        ]);

        return [$merchant];
    }

    /**
     * Scenario: Soft limit breach
     * Expectation: 1th Escalation should be raised and merchant should be sent communication
     */
    public function testSoftLimitEscalation1()
    {
        $this->createAndFetchMocks();
        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId('100000razorpay');

        [$merchant] = $this->createAndFetchFixtures();

        $this->createTransaction($merchant->getId(), 'payment', 1000);

        (new EscalationCore())->handleSoftLimitBreach();

        $wf_action = $this->getDbLastEntity('workflow_action', 'live');
        $escalation = $this->getDbLastEntity('merchant_auto_kyc_escalations', 'live');
        self::assertNotEmpty($escalation);
        self::assertEquals('soft_limit', $escalation->getAttribute('escalation_type'));
        self::assertEquals('1', $escalation->getAttribute('escalation_level'));
        self::assertNotEmpty($wf_action);
        self::assertEquals($escalation->getAttribute('workflow_id'), $wf_action->getId());

        $escalationv2 = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');
        self::assertNotEmpty($escalationv2);
        self::assertEquals('merchant', $escalationv2->getAttribute('escalated_to'));
        self::assertEquals('soft_limit_level_1', $escalationv2->getAttribute('milestone'));

        //verify email has been sent
        Mail::assertQueued(MerchantOnboardingEmail::class, function($mail) {
            $this->assertEquals('emails.merchant.onboarding.activated_mcc_pending_soft_limit_breach', $mail->getTemplate());

            return true;
        });

    }
    /**
     * Scenario: Soft limit breach
     * Expectation: 1th Escalation should be raised and merchant should not be sent communication
     */
    public function testSoftLimitEscalation1WithoutWebsite()
    {
        $this->createAndFetchMocks();
        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId('100000razorpay');
        [$merchant] = $this->createAndFetchFixtures();
        $this->createTransaction($merchant->getId(), 'payment', 1000);
        $data = [
            'business_website'     => null
        ];
        $this->fixtures->on('live')->edit('merchant_detail', $merchant->getId(), $data);
        (new EscalationCore())->handleSoftLimitBreach();
        $escalation = $this->getDbLastEntity('merchant_auto_kyc_escalations', 'live');
        self::assertNotEmpty($escalation);
        self::assertEquals('soft_limit', $escalation->getAttribute('escalation_type'));
        self::assertEquals('1', $escalation->getAttribute('escalation_level'));
        $escalationv2 = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');
        self::assertEmpty($escalationv2);
        //verify email has been sent
        Mail::assertNotQueued(MerchantOnboardingEmail::class);
    }

    /**
     * Scenario: T+1 days after hard limit breach
     * Expectation: 2th Escalation should be raised and communication should go
     */
    public function testHardLimitEscalation2()
    {
        $this->createAndFetchMocks();
        $this->app->instance("rzp.mode", Mode::LIVE);

        $merchant = $this->fixtures->on('live')->create('merchant', [
            'live'       => true,
            'activated'  => 1,
            'hold_funds' => false
        ]);

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'merchant_id'       => $merchant['id'],
            'activation_status' => 'activated_mcc_pending',
            'business_website'  => 'http://hello.com'
        ]);

        $this->createTransaction($merchant->getId(), 'payment', 15000);

        $escalations= $this->fixtures->on('live')->create('merchant_auto_kyc_escalations', [
            'merchant_id'       => $merchant['id'],
            'escalation_level'  => 1,
            'escalation_method' => 'email',
            'escalation_type'   => 'hard_limit',
            'created_at'        => Carbon::now(Timezone::IST)->subDays(2)->getTimestamp()
        ]);
        (new EscalationCore)->handleEscalationsCron();

        $escalation = $this->getDbLastEntity('merchant_auto_kyc_escalations', 'live');
        self::assertNotEmpty($escalation);
        self::assertEquals('hard_limit', $escalation->getAttribute('escalation_type'));
        self::assertEquals('2', $escalation->getAttribute('escalation_level'));

        $escalationv2 = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');
        self::assertNotEmpty($escalationv2);
        self::assertEquals('merchant', $escalationv2->getAttribute('escalated_to'));
        self::assertEquals('hard_limit_level_2', $escalationv2->getAttribute('milestone'));

        //verify email has been sent
        Mail::assertQueued(MerchantOnboardingEmail::class, function($mail) {
            $this->assertEquals('emails.merchant.onboarding.activated_mcc_pending_hard_limit_breach', $mail->getTemplate());

            return true;
        });
    }


    /**
     * Scenario: T+5 days after hard limit breach
     * Expectation: 4th Escalation should be raised and merchant should put in FOH. Live should be enabled.
     */
    public function testHardLimitEscalation4()
    {
        $this->createAndFetchMocks();
        $this->app->instance("rzp.mode", Mode::LIVE);

        $merchant = $this->fixtures->on('live')->create('merchant', [
            'live'       => true,
            'activated'  => 1,
            'hold_funds' => false
        ]);

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'merchant_id'       => $merchant['id'],
            'activation_status' => 'activated_mcc_pending',
            'business_website'  => "http://hello.com"
        ]);

        $this->fixtures->on('live')->create('merchant_auto_kyc_escalations', [
            'merchant_id'       => $merchant['id'],
            'escalation_level'  => 3,
            'escalation_method' => 'email',
            'escalation_type'   => 'hard_limit',
            'created_at'        => Carbon::now(Timezone::IST)->subDays(5)->getTimestamp()
        ]);
        $this->createTransaction($merchant->getId(), 'payment', 1500000);

        (new EscalationCore)->handleEscalationsCron();

        $merchant = $this->getDbEntityById('merchant', $merchant['id']);

        $this->assertTrue($merchant->getAttribute(MerchantEntity::LIVE));
        $this->assertEquals(1, $merchant->getAttribute(MerchantEntity::ACTIVATED));

        $this->assertTrue($merchant->getAttribute(MerchantEntity::HOLD_FUNDS));
        $this->assertEquals(
            EscalationConstant::HOLD_FUNDS_REASON_FOR_LIMIT_BREACH,
            $merchant->getAttribute(MerchantEntity::HOLD_FUNDS_REASON)
        );

        $escalation = $this->getDbLastEntity('merchant_auto_kyc_escalations', 'live');
        self::assertNotEmpty($escalation);
        self::assertEquals('hard_limit', $escalation->getAttribute('escalation_type'));
        self::assertEquals('4', $escalation->getAttribute('escalation_level'));

        $escalationv2 = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');
        self::assertNotEmpty($escalationv2);
        self::assertEquals('merchant', $escalationv2->getAttribute('escalated_to'));
        self::assertEquals('hard_limit_level_4', $escalationv2->getAttribute('milestone'));

        //verify email has been sent
        Mail::assertQueued(MerchantOnboardingEmail::class, function($mail) {
            $this->assertEquals('emails.merchant.onboarding.funds_on_hold', $mail->getTemplate());

            return true;
        });
    }


    /**
     * Scenario: T+12 days after hard limit breach
     * Expectation: 5th Escalation should be raised.
     */
    public function testFOHReminder1()
    {
        $this->createAndFetchMocks();
        $this->app->instance("rzp.mode", Mode::LIVE);

        $merchant = $this->fixtures->on('live')->create('merchant', [
            'live'       => true,
            'activated'  => 1,
            'hold_funds' => true
        ]);

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'merchant_id'       => $merchant['id'],
            'activation_status' => 'activated_mcc_pending',
            'business_website'     => 'http://hello.com'
        ]);

        $this->fixtures->on('live')->create('merchant_auto_kyc_escalations', [
            'merchant_id'       => $merchant['id'],
            'escalation_level'  => 4,
            'escalation_method' => 'email',
            'escalation_type'   => 'hard_limit',
            'created_at'        => Carbon::now(Timezone::IST)->subDays(7)->getTimestamp()
        ]);
        $this->createTransaction($merchant->getId(), 'payment', 1500000);

        (new EscalationCore)->handleEscalationsCron();

        $escalation = $this->getDbLastEntity('merchant_auto_kyc_escalations', 'live');
        self::assertNotEmpty($escalation);
        self::assertEquals('hard_limit', $escalation->getAttribute('escalation_type'));
        self::assertEquals('5', $escalation->getAttribute('escalation_level'));

        $escalationv2 = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');
        self::assertNotEmpty($escalationv2);
        self::assertEquals('merchant', $escalationv2->getAttribute('escalated_to'));
        self::assertEquals('funds_on_hold_reminder', $escalationv2->getAttribute('milestone'));

        //verify email has been sent
        Mail::assertQueued(MerchantOnboardingEmail::class, function($mail) {
            $this->assertEquals('emails.merchant.onboarding.funds_on_hold_reminder', $mail->getTemplate());

            return true;
        });
    }
    /**
     * Scenario: T+19 days after hard limit breach
     * Expectation: 6th Escalation should be raised
     */
    public function testFOHReminder2()
    {
        $this->createAndFetchMocks();
        $this->app->instance("rzp.mode", Mode::LIVE);

        $merchant = $this->fixtures->on('live')->create('merchant', [
            'live'       => true,
            'activated'  => 1,
            'hold_funds' => true
        ]);

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'merchant_id'       => $merchant['id'],
            'activation_status' => 'activated_mcc_pending',
            'business_website'     => null
        ]);

        $this->fixtures->on('live')->create('merchant_auto_kyc_escalations', [
            'merchant_id'       => $merchant['id'],
            'escalation_level'  => 5,
            'escalation_method' => 'email',
            'escalation_type'   => 'hard_limit',
            'created_at'        => Carbon::now(Timezone::IST)->subDays(7)->getTimestamp()
        ]);
        $this->createTransaction($merchant->getId(), 'payment', 1500000);

        (new EscalationCore)->handleEscalationsCron();

        $escalation = $this->getDbLastEntity('merchant_auto_kyc_escalations', 'live');
        self::assertNotEmpty($escalation);
        self::assertEquals('hard_limit', $escalation->getAttribute('escalation_type'));
        self::assertEquals('6', $escalation->getAttribute('escalation_level'));

        $escalationv2 = $this->getDbLastEntity('merchant_onboarding_escalations', 'live');
        self::assertNotEmpty($escalationv2);
        self::assertEquals('merchant', $escalationv2->getAttribute('escalated_to'));
        self::assertEquals('funds_on_hold_reminder', $escalationv2->getAttribute('milestone'));

        //verify email has been sent
        Mail::assertQueued(MerchantOnboardingEmail::class, function($mail) {
            $this->assertEquals('emails.merchant.onboarding.funds_on_hold_reminder', $mail->getTemplate());

            return true;
        });
    }
}
